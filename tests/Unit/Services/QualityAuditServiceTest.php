<?php

declare(strict_types=1);

use App\Enums\AuditStatus;
use App\Enums\EcartGravite;
use App\Enums\PacSource;
use App\Enums\PlanAmeliorationStatus;
use App\Models\AuditEcart;
use App\Models\PlanAmelioration;
use App\Models\QualityAudit;
use App\Models\Structure;
use App\Models\User;
use App\Services\QualityAuditService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->structure = Structure::factory()->create();
    $this->qualite = User::factory()->forStructure($this->structure)->state(['type' => 'referent_qualite'])->create();

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->structure->getKey());
    $this->qualite->assignRole('referent_qualite');

    app()->instance('current_structure', $this->structure);

    $this->service = new QualityAuditService;
});

it('creates an audit in planifie state', function () {
    $audit = $this->service->create([
        'titre' => 'Test audit',
        'referentiel' => 'has',
    ], $this->qualite);

    expect($audit->statut)->toBe(AuditStatus::Planifie)
        ->and($audit->created_by)->toBe($this->qualite->id)
        ->and($audit->structure_id)->toBe($this->structure->id);
});

it('promotes audit from planifie to en_cours when first ecart is added', function () {
    $audit = QualityAudit::factory()->forStructure($this->structure)->createdBy($this->qualite)->create();

    $this->service->addEcart($audit, [
        'critere' => 'Test',
        'constat' => 'Constat de test',
        'gravite' => 'mineur',
    ], $this->qualite);

    expect($audit->fresh()->statut)->toBe(AuditStatus::EnCours);
});

it('cascades a major ecart into a PAC entry when create_pac is true', function () {
    $audit = QualityAudit::factory()->forStructure($this->structure)->createdBy($this->qualite)->create();

    $result = $this->service->addEcart($audit, [
        'critere' => 'Traçabilité',
        'constat' => 'Pas de cahier numérique',
        'gravite' => EcartGravite::Majeur->value,
        'create_pac' => true,
    ], $this->qualite);

    expect($result['pac'])->not->toBeNull()
        ->and($result['pac']->source)->toBe(PacSource::Audit)
        ->and($result['pac']->source_id)->toBe($audit->id)
        ->and($result['pac']->statut)->toBe(PlanAmeliorationStatus::Ouvert);
});

it('does not cascade into a PAC for minor ecarts even with create_pac=true', function () {
    $audit = QualityAudit::factory()->forStructure($this->structure)->createdBy($this->qualite)->create();

    $result = $this->service->addEcart($audit, [
        'critere' => 'Affichage',
        'constat' => 'Affiche déchirée',
        'gravite' => EcartGravite::Mineur->value,
        'create_pac' => true,
    ], $this->qualite);

    expect($result['pac'])->toBeNull();
    expect(PlanAmelioration::query()->count())->toBe(0);
});

it('refuses to update a finalized audit', function () {
    $audit = QualityAudit::factory()->forStructure($this->structure)->createdBy($this->qualite)->termine(85)->create();

    $this->service->update($audit, ['titre' => 'New title']);
})->throws(HttpException::class);

it('computes score from weighted ecarts', function () {
    $audit = QualityAudit::factory()->forStructure($this->structure)->createdBy($this->qualite)->create();

    AuditEcart::factory()->forAudit($audit)->create(['gravite' => EcartGravite::Mineur->value]); // weight 1
    AuditEcart::factory()->forAudit($audit)->majeur()->create(); // weight 3
    // Baseline 20: penalty = (4/20) * 100 = 20%, score = 80

    $audit = $audit->fresh()->load('ecarts');
    expect($this->service->computeScore($audit))->toBe(80);
});

it('finalizes the audit with computed score and locks it', function () {
    $audit = QualityAudit::factory()->forStructure($this->structure)->createdBy($this->qualite)->enCours()->create();
    AuditEcart::factory()->forAudit($audit)->critique()->create(); // weight 5 → score 75
    $audit->load('ecarts');

    $finalized = $this->service->finalize($audit);

    expect($finalized->statut)->toBe(AuditStatus::Termine)
        ->and($finalized->score)->toBe(75)
        ->and($finalized->finalized_at)->not->toBeNull();
});

it('cancels an audit with a reason', function () {
    $audit = QualityAudit::factory()->forStructure($this->structure)->createdBy($this->qualite)->create();

    $cancelled = $this->service->cancel($audit, 'Audit reporté');

    expect($cancelled->statut)->toBe(AuditStatus::Annule)
        ->and($cancelled->cancellation_reason)->toBe('Audit reporté')
        ->and($cancelled->cancelled_at)->not->toBeNull();
});

it('refuses double-finalize', function () {
    $audit = QualityAudit::factory()->forStructure($this->structure)->createdBy($this->qualite)->termine()->create();

    $this->service->finalize($audit);
})->throws(HttpException::class);
