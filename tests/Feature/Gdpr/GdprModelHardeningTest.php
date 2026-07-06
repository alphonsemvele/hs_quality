<?php

declare(strict_types=1);

use App\Models\AccountDeletionRequest;
use App\Models\DataExportRequest;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

// ── Cross-tenant leak guard (rule #3) ───────────────────────────────────────

it('does not leak account deletion requests across tenants', function (): void {
    ['structureA' => $a, 'structureB' => $b, 'userA' => $ua, 'userB' => $ub] = twoStructures();

    AccountDeletionRequest::factory()->create(['structure_id' => $a->id, 'user_id' => $ua->id]);
    AccountDeletionRequest::factory()->create(['structure_id' => $b->id, 'user_id' => $ub->id]);

    actingAsStructure($ua);

    $rows = AccountDeletionRequest::query()->get();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()->structure_id)->toBe($a->id)
        ->and(AccountDeletionRequest::query()->where('structure_id', $b->id)->count())->toBe(0);
});

it('does not leak data export requests across tenants', function (): void {
    ['structureA' => $a, 'structureB' => $b, 'userA' => $ua, 'userB' => $ub] = twoStructures();

    DataExportRequest::factory()->create(['structure_id' => $a->id, 'user_id' => $ua->id]);
    DataExportRequest::factory()->create(['structure_id' => $b->id, 'user_id' => $ub->id]);

    actingAsStructure($ua);

    $rows = DataExportRequest::query()->get();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()->structure_id)->toBe($a->id)
        ->and(DataExportRequest::query()->where('structure_id', $b->id)->count())->toBe(0);
});

// ── Gate authorization via Policy (exercises BasePolicy::before tenant check) ─

it('denies a foreign-tenant user any access to an export request', function (): void {
    $structure = Structure::factory()->create();
    app()->instance('current_structure', $structure);
    $owner = User::factory()->forStructure($structure)->create();
    $export = DataExportRequest::factory()->create([
        'structure_id' => $structure->id,
        'user_id' => $owner->id,
    ]);

    $foreignStructure = Structure::factory()->create();
    $foreign = User::factory()->forStructure($foreignStructure)->create();

    expect($foreign->can('view', $export))->toBeFalse()
        ->and($foreign->can('download', $export))->toBeFalse()
        ->and($owner->can('view', $export))->toBeTrue()
        ->and($owner->can('download', $export))->toBeTrue();
});

it('denies a same-tenant non-owner access to another user\'s deletion request', function (): void {
    $structure = Structure::factory()->create();
    app()->instance('current_structure', $structure);
    $owner = User::factory()->forStructure($structure)->create();
    $sibling = User::factory()->forStructure($structure)->create();

    $deletion = AccountDeletionRequest::factory()->create([
        'structure_id' => $structure->id,
        'user_id' => $owner->id,
    ]);

    expect($sibling->can('view', $deletion))->toBeFalse()
        ->and($sibling->can('cancel', $deletion))->toBeFalse()
        ->and($owner->can('view', $deletion))->toBeTrue()
        ->and($owner->can('cancel', $deletion))->toBeTrue();
});

// ── Auditable (rule #3) ──────────────────────────────────────────────────────

it('audits creation and cancellation of an account deletion request', function (): void {
    $user = actingAsRole('coordinateur');

    $this->post('/dashboard/profile/gdpr/delete-account', ['confirm' => true]);
    $request = AccountDeletionRequest::query()->where('user_id', $user->id)->first();

    expect($request->audits()->where('event', 'created')->exists())->toBeTrue()
        ->and($request->audits()->first()->structure_id)->toBe($user->structure_id);

    $this->post('/dashboard/profile/gdpr/delete-account/cancel');

    expect($request->fresh()->audits()->where('event', 'updated')->exists())->toBeTrue();
});

it('audits creation of a data export request', function (): void {
    Queue::fake();
    $user = actingAsRole('intervenant');

    $this->post('/dashboard/profile/gdpr/export');
    $export = DataExportRequest::query()->where('user_id', $user->id)->first();

    expect($export->audits()->where('event', 'created')->exists())->toBeTrue()
        ->and($export->audits()->first()->structure_id)->toBe($user->structure_id);
});
