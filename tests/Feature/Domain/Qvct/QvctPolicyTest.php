<?php

declare(strict_types=1);

use App\Models\QvctCampaign;
use App\Models\QvctQuestionnaire;
use App\Models\QvctResponse;
use App\Models\QvctWeakSignal;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);
});

function makeQvctUser(string $role, Structure $structure): User
{
    $user = User::factory()->forStructure($structure)->state(['type' => $role])->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($structure->getKey());
    $user->assignRole($role);

    return $user;
}

// ── QvctQuestionnaire ──────────────────────────────────────────────────────

it('rh can manage questionnaires', function (): void {
    $rh = makeQvctUser('rh', $this->structure);

    expect($rh->can('create', QvctQuestionnaire::class))->toBeTrue();

    $q = QvctQuestionnaire::factory()->forStructure($this->structure)->create();
    expect($rh->can('view', $q))->toBeTrue()
        ->and($rh->can('update', $q))->toBeTrue()
        ->and($rh->can('delete', $q))->toBeTrue();
});

it('intervenant cannot manage questionnaires but can view active templates', function (): void {
    $intervenant = makeQvctUser('intervenant', $this->structure);

    expect($intervenant->can('create', QvctQuestionnaire::class))->toBeFalse();

    $active = QvctQuestionnaire::factory()->forStructure($this->structure)->create(['is_active' => true]);
    $inactive = QvctQuestionnaire::factory()->forStructure($this->structure)->create(['is_active' => false]);

    expect($intervenant->can('view', $active))->toBeTrue()
        ->and($intervenant->can('view', $inactive))->toBeFalse()
        ->and($intervenant->can('update', $active))->toBeFalse();
});

// ── QvctCampaign ───────────────────────────────────────────────────────────

it('rh can manage campaigns', function (): void {
    $rh = makeQvctUser('rh', $this->structure);
    $campaign = QvctCampaign::factory()->forStructure($this->structure)->create();

    expect($rh->can('create', QvctCampaign::class))->toBeTrue()
        ->and($rh->can('update', $campaign))->toBeTrue()
        ->and($rh->can('close', $campaign))->toBeTrue()
        ->and($rh->can('respond', $campaign))->toBeTrue();
});

it('intervenant can respond to campaigns but cannot manage them', function (): void {
    $intervenant = makeQvctUser('intervenant', $this->structure);
    $campaign = QvctCampaign::factory()->forStructure($this->structure)->create();

    expect($intervenant->can('respond', $campaign))->toBeTrue()
        ->and($intervenant->can('view', $campaign))->toBeTrue()
        ->and($intervenant->can('create', QvctCampaign::class))->toBeFalse()
        ->and($intervenant->can('update', $campaign))->toBeFalse()
        ->and($intervenant->can('close', $campaign))->toBeFalse();
});

it('blocks all access from a foreign-tenant user', function (): void {
    $foreignStructure = Structure::factory()->create();
    $foreignRh = makeQvctUser('rh', $foreignStructure);

    // Re-bind to the local structure for the resource lookup.
    app()->instance('current_structure', $this->structure);
    $localCampaign = QvctCampaign::factory()->forStructure($this->structure)->create();

    expect($foreignRh->can('view', $localCampaign))->toBeFalse()
        ->and($foreignRh->can('update', $localCampaign))->toBeFalse()
        ->and($foreignRh->can('respond', $localCampaign))->toBeFalse();
});

// ── QvctResponse — never accessible per anonymity invariant ────────────────

it('no role can view a single QvctResponse — anonymity guard', function (): void {
    $rh = makeQvctUser('rh', $this->structure);
    $dirigeant = makeQvctUser('dirigeant', $this->structure);
    $intervenant = makeQvctUser('intervenant', $this->structure);

    $campaign = QvctCampaign::factory()->forStructure($this->structure)->create();
    $response = QvctResponse::factory()->forCampaign($campaign)->create();

    foreach ([$rh, $dirigeant, $intervenant] as $user) {
        expect($user->can('viewAny', QvctResponse::class))->toBeFalse();
        expect($user->can('view', $response))->toBeFalse();
        expect($user->can('update', $response))->toBeFalse();
        expect($user->can('delete', $response))->toBeFalse();
    }
});

// ── QvctWeakSignal ─────────────────────────────────────────────────────────

it('rh can view + acknowledge weak signals; intervenant cannot view', function (): void {
    $rh = makeQvctUser('rh', $this->structure);
    $intervenant = makeQvctUser('intervenant', $this->structure);

    $campaign = QvctCampaign::factory()->forStructure($this->structure)->create();
    $signal = QvctWeakSignal::factory()->forCampaign($campaign)->create();

    expect($rh->can('view', $signal))->toBeTrue()
        ->and($rh->can('acknowledge', $signal))->toBeTrue()
        ->and($intervenant->can('view', $signal))->toBeFalse()
        ->and($intervenant->can('acknowledge', $signal))->toBeFalse();
});
