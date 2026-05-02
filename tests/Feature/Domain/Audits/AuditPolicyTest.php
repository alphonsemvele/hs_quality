<?php

declare(strict_types=1);

use App\Models\AuditGrid;
use App\Models\AuditGridItem;
use App\Models\AuditRun;
use App\Models\AuditRunResponse;
use App\Models\Pac;
use App\Models\PacAction;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);
});

function makeAuditUser(string $role, Structure $structure): User
{
    $user = User::factory()->forStructure($structure)->state(['type' => $role])->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($structure->getKey());
    $user->assignRole($role);

    return $user;
}

// ── AuditGrid ─────────────────────────────────────────────────────────────────

it('referent_qualite can configure grids; intervenant cannot', function (): void {
    $rq = makeAuditUser('referent_qualite', $this->structure);
    $intervenant = makeAuditUser('intervenant', $this->structure);
    $grid = AuditGrid::factory()->forStructure($this->structure)->create();

    expect($rq->can('view', $grid))->toBeTrue()
        ->and($rq->can('create', AuditGrid::class))->toBeTrue()
        ->and($rq->can('update', $grid))->toBeTrue()
        ->and($intervenant->can('view', $grid))->toBeFalse()
        ->and($intervenant->can('create', AuditGrid::class))->toBeFalse();
});

it('coordinateur can view grids (operational audit prep) but not configure them', function (): void {
    $coord = makeAuditUser('coordinateur', $this->structure);
    $grid = AuditGrid::factory()->forStructure($this->structure)->create();

    expect($coord->can('view', $grid))->toBeTrue()
        ->and($coord->can('create', AuditGrid::class))->toBeFalse()
        ->and($coord->can('update', $grid))->toBeFalse();
});

// ── AuditRun ──────────────────────────────────────────────────────────────────

it('referent_qualite can start + finalise runs', function (): void {
    $rq = makeAuditUser('referent_qualite', $this->structure);
    $grid = AuditGrid::factory()->forStructure($this->structure)->create();
    $run = AuditRun::factory()->forGrid($grid)->create();

    expect($rq->can('create', AuditRun::class))->toBeTrue()
        ->and($rq->can('finalise', $run))->toBeTrue();
});

it('intervenant cannot create or finalise runs', function (): void {
    $intervenant = makeAuditUser('intervenant', $this->structure);
    $grid = AuditGrid::factory()->forStructure($this->structure)->create();
    $run = AuditRun::factory()->forGrid($grid)->create();

    expect($intervenant->can('create', AuditRun::class))->toBeFalse()
        ->and($intervenant->can('finalise', $run))->toBeFalse();
});

// ── AuditRunResponse ──────────────────────────────────────────────────────────

it('referent_qualite can record responses; coordinateur cannot', function (): void {
    $rq = makeAuditUser('referent_qualite', $this->structure);
    $coord = makeAuditUser('coordinateur', $this->structure);
    $grid = AuditGrid::factory()->forStructure($this->structure)->create();
    $item = AuditGridItem::factory()->forGrid($grid)->create();
    $run = AuditRun::factory()->forGrid($grid)->create();
    $response = AuditRunResponse::factory()->forRunAndItem($run, $item)->create();

    expect($rq->can('create', AuditRunResponse::class))->toBeTrue()
        ->and($rq->can('update', $response))->toBeTrue()
        ->and($coord->can('create', AuditRunResponse::class))->toBeFalse()
        ->and($coord->can('update', $response))->toBeFalse();
});

// ── Pac ───────────────────────────────────────────────────────────────────────

it('referent_qualite + dirigeant can generate / update / close PACs', function (): void {
    $rq = makeAuditUser('referent_qualite', $this->structure);
    $dirigeant = makeAuditUser('dirigeant', $this->structure);
    $pac = Pac::factory()->forStructure($this->structure)->create();

    foreach ([$rq, $dirigeant] as $user) {
        expect($user->can('create', Pac::class))->toBeTrue();
        expect($user->can('update', $pac))->toBeTrue();
        expect($user->can('close', $pac))->toBeTrue();
    }
});

it('rh cannot manage PACs (PACs are quality-team scope, RH owns QVCT)', function (): void {
    $rh = makeAuditUser('rh', $this->structure);
    $pac = Pac::factory()->forStructure($this->structure)->create();

    expect($rh->can('create', Pac::class))->toBeFalse()
        ->and($rh->can('update', $pac))->toBeFalse();
});

it('intervenant cannot manage PACs', function (): void {
    $intervenant = makeAuditUser('intervenant', $this->structure);
    $pac = Pac::factory()->forStructure($this->structure)->create();

    expect($intervenant->can('create', Pac::class))->toBeFalse()
        ->and($intervenant->can('view', $pac))->toBeFalse();
});

// ── PacAction ─────────────────────────────────────────────────────────────────

it('responsible_user can update their own pac action even without pac.update', function (): void {
    $coord = makeAuditUser('coordinateur', $this->structure);
    $pac = Pac::factory()->forStructure($this->structure)->create();
    $action = PacAction::factory()->forPac($pac)->create([
        'responsible_user_id' => $coord->id,
    ]);

    expect($coord->can('update', $action))->toBeTrue();
});

it('intervenant cannot update an action assigned to someone else (responsible_user-only path)', function (): void {
    // Intervenant lacks pac.update entirely — only the responsible_user
    // bypass would let them update; verify they don't get someone else's
    // assigned action.
    $intervenant = makeAuditUser('intervenant', $this->structure);
    $pac = Pac::factory()->forStructure($this->structure)->create();
    $action = PacAction::factory()->forPac($pac)->create(); // no responsible_user

    expect($intervenant->can('update', $action))->toBeFalse();
});

// ── Cross-tenant ──────────────────────────────────────────────────────────────

it('foreign-tenant referent_qualite cannot view a local PAC', function (): void {
    $foreignStructure = Structure::factory()->create();
    $foreignRq = makeAuditUser('referent_qualite', $foreignStructure);

    app()->instance('current_structure', $this->structure);
    $local = Pac::factory()->forStructure($this->structure)->create();

    expect($foreignRq->can('view', $local))->toBeFalse();
});
