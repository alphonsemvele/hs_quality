<?php

declare(strict_types=1);

use App\Models\AuditGrid;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;

/**
 * Feature coverage for AuditGrid create / update / destroy by tenant users.
 * Custom (internal) référentiels are authored from the dashboard;
 * standard HAS / ISO / AFNOR grids are seeded and remain read-only
 * to the controller (though the policy allows update on the tenant's copy).
 *
 * Includes a cross-tenant leak guard: a user from structure A must never
 * be able to update or delete a grid that belongs to structure B.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('renders the create page for users who can configure audits', function (): void {
    actingAsRole('referent_qualite');

    $this->get('/audits/grids/create')->assertSuccessful();
});

it('forbids the create page for users without audits.configure', function (): void {
    actingAsRole('intervenant');

    $this->get('/audits/grids/create')->assertForbidden();
});

it('creates a custom référentiel scoped to the current tenant', function (): void {
    $user = actingAsRole('referent_qualite');

    $response = $this->post('/audits/grids', [
        'title' => 'Référentiel interne — bientraitance',
        'description' => 'Adapté du HAS pour notre structure.',
    ]);

    $grid = AuditGrid::query()->where('title', 'Référentiel interne — bientraitance')->first();

    expect($grid)->not->toBeNull()
        ->and($grid->structure_id)->toBe($user->structure_id)
        ->and($grid->source->value)->toBe('custom')
        ->and($grid->is_active)->toBeTrue();

    $response->assertRedirect("/audits/grids/{$grid->id}");
});

it('rejects creation with an empty title', function (): void {
    actingAsRole('referent_qualite');

    $this->post('/audits/grids', ['title' => '', 'description' => 'x'])
        ->assertSessionHasErrors('title');
});

it('updates a référentiel and persists is_active toggling', function (): void {
    $user = actingAsRole('referent_qualite');

    $grid = AuditGrid::factory()->forStructure(Structure::find($user->structure_id))->create([
        'title' => 'Initial',
        'is_active' => true,
    ]);

    $this->put("/audits/grids/{$grid->id}", [
        'title' => 'Renommé',
        'description' => null,
        'is_active' => false,
    ])->assertRedirect("/audits/grids/{$grid->id}");

    $grid->refresh();
    expect($grid->title)->toBe('Renommé')
        ->and($grid->is_active)->toBeFalse();
});

it('soft-deletes a référentiel via destroy', function (): void {
    $user = actingAsRole('referent_qualite');

    $grid = AuditGrid::factory()->forStructure(Structure::find($user->structure_id))->create();

    $this->delete("/audits/grids/{$grid->id}")
        ->assertRedirect('/audits/grids');

    expect(AuditGrid::find($grid->id))->toBeNull()
        ->and(AuditGrid::withTrashed()->find($grid->id))->not->toBeNull();
});

it('returns 404 when updating a référentiel from another tenant', function (): void {
    $other = Structure::factory()->create();
    $foreign = AuditGrid::factory()->forStructure($other)->create();

    actingAsRole('referent_qualite');

    $this->put("/audits/grids/{$foreign->id}", [
        'title' => 'Tentative cross-tenant',
        'description' => null,
        'is_active' => true,
    ])->assertNotFound();

    expect($foreign->fresh()->title)->not->toBe('Tentative cross-tenant');
});

it('returns 404 when deleting a référentiel from another tenant', function (): void {
    $other = Structure::factory()->create();
    $foreign = AuditGrid::factory()->forStructure($other)->create();

    actingAsRole('referent_qualite');

    $this->delete("/audits/grids/{$foreign->id}")->assertNotFound();

    $persisted = AuditGrid::withoutGlobalScopes()->withTrashed()->find($foreign->id);
    expect($persisted)->not->toBeNull()
        ->and($persisted->trashed())->toBeFalse();
});

it('forbids update for users without audits.configure', function (): void {
    $user = actingAsRole('intervenant');
    $grid = AuditGrid::factory()->forStructure(Structure::find($user->structure_id))->create();

    $this->put("/audits/grids/{$grid->id}", [
        'title' => 'Nope',
        'description' => null,
        'is_active' => true,
    ])->assertForbidden();
});
