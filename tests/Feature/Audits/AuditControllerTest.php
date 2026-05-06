<?php

declare(strict_types=1);

use App\Enums\AuditStatus;
use App\Enums\EcartGravite;
use App\Models\AuditEcart;
use App\Models\PlanAmelioration;
use App\Models\QualityAudit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('blocks an intervenant from listing audits', function () {
    $intervenant = actingAsRole('intervenant');

    $this->get('/audits')->assertForbidden();
});

it('lets a referent_qualite list audits', function () {
    actingAsRole('referent_qualite');

    $this->get('/audits')->assertSuccessful();
});

it('lets a dirigeant create an audit', function () {
    actingAsRole('dirigeant');

    $this->post('/audits', [
        'titre' => 'Audit HAS test',
        'referentiel' => 'has',
        'date_audit' => now()->format('Y-m-d'),
    ])->assertRedirect();

    expect(QualityAudit::query()->where('titre', 'Audit HAS test')->exists())->toBeTrue();
});

it('blocks a coordinateur from creating an audit', function () {
    actingAsRole('coordinateur');

    $this->post('/audits', [
        'titre' => 'Should be blocked',
        'referentiel' => 'has',
    ])->assertForbidden();
});

it('adds an ecart and cascades into a PAC for major findings when requested', function () {
    $user = actingAsRole('referent_qualite');
    $audit = QualityAudit::factory()->forStructure($user->structure)->createdBy($user)->create();

    $this->post("/audits/{$audit->id}/ecarts", [
        'critere' => 'Traçabilité',
        'constat' => 'Pas de cahier numérique',
        'gravite' => EcartGravite::Majeur->value,
        'create_pac' => true,
    ])->assertRedirect();

    expect(AuditEcart::query()->where('quality_audit_id', $audit->id)->count())->toBe(1)
        ->and(PlanAmelioration::query()->where('source_id', $audit->id)->count())->toBe(1);
});

it('finalizes the audit, computes score, and locks further changes', function () {
    $user = actingAsRole('referent_qualite');
    $audit = QualityAudit::factory()->forStructure($user->structure)->createdBy($user)->enCours()->create();
    AuditEcart::factory()->forAudit($audit)->majeur()->create(); // weight 3 → score 85

    $this->post("/audits/{$audit->id}/finaliser")->assertRedirect();

    $audit->refresh();
    expect($audit->statut)->toBe(AuditStatus::Termine)
        ->and($audit->score)->toBe(85);

    // Subsequent ecart add must fail (policy blocks first → 403).
    $this->post("/audits/{$audit->id}/ecarts", [
        'critere' => 'X', 'constat' => 'Y', 'gravite' => 'mineur',
    ])->assertForbidden();

    expect(AuditEcart::query()->where('quality_audit_id', $audit->id)->count())->toBe(1);
});

it('lets a referent_qualite open the edit page for a non-terminal audit', function () {
    $user = actingAsRole('referent_qualite');
    $audit = QualityAudit::factory()->forStructure($user->structure)->createdBy($user)->create();

    $this->get("/audits/{$audit->id}/edit")
        ->assertSuccessful()
        ->assertInertia(fn ($p) => $p
            ->component('dashboard/audits/edit')
            ->where('audit.id', $audit->id)
            ->where('audit.titre', $audit->titre));
});

it('blocks the edit page once the audit is finalized', function () {
    $user = actingAsRole('referent_qualite');
    $audit = QualityAudit::factory()->forStructure($user->structure)->createdBy($user)->termine()->create();

    $this->get("/audits/{$audit->id}/edit")->assertForbidden();
});

it('updates the audit metadata via PUT', function () {
    $user = actingAsRole('referent_qualite');
    $audit = QualityAudit::factory()->forStructure($user->structure)->createdBy($user)->create();

    $this->put("/audits/{$audit->id}", [
        'titre' => 'Titre révisé',
        'auditeur' => 'Nouveau cabinet',
    ])->assertRedirect();

    expect($audit->fresh()->titre)->toBe('Titre révisé')
        ->and($audit->fresh()->auditeur)->toBe('Nouveau cabinet');
});

it('does not leak audits across tenants', function () {
    ['userA' => $userA, 'userB' => $userB, 'structureA' => $sa, 'structureB' => $sb] = twoStructures();

    // Re-seed roles for each tenant so coordinateur has audits.view.
    // Actually only referent_qualite/dirigeant have audits.view in our matrix;
    // give each user the proper role first.
    app(PermissionRegistrar::class)->setPermissionsTeamId($sa->getKey());
    $userA->syncRoles([]);
    $userA->update(['type' => 'referent_qualite']);
    $userA->assignRole('referent_qualite');

    app(PermissionRegistrar::class)->setPermissionsTeamId($sb->getKey());
    $userB->syncRoles([]);
    $userB->update(['type' => 'referent_qualite']);
    $userB->assignRole('referent_qualite');

    QualityAudit::factory()->forStructure($sa)->createdBy($userA)->count(2)->create();
    QualityAudit::factory()->forStructure($sb)->createdBy($userB)->count(3)->create();

    actingAsStructure($userA);
    $listA = QualityAudit::query()->get();
    expect($listA)->toHaveCount(2)
        ->and($listA->every(fn ($a) => $a->structure_id === $sa->id))->toBeTrue();

    actingAsStructure($userB);
    $listB = QualityAudit::query()->get();
    expect($listB)->toHaveCount(3)
        ->and($listB->every(fn ($a) => $a->structure_id === $sb->id))->toBeTrue();
});
