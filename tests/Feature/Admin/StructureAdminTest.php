<?php

declare(strict_types=1);

use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

/**
 * Wave 0 / #37 — operator surface for managing tenants.
 *
 * Coverage:
 *   - super_admin can list / create / show / update / delete structures
 *   - tenant-scoped users (intervenant, coordinateur, dirigeant) get 404
 *     on every endpoint (surface invisible — EnsureSuperAdmin middleware)
 *   - unauthenticated requests redirect to login
 *   - validation rejects bad input
 *   - provisioning creates Structure + Dirigeant atomically through HTTP
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

function actingAsSuperAdmin(): User
{
    $admin = User::factory()->create([
        'first_name' => 'Op',
        'last_name' => 'Admin',
        'email' => 'op'.uniqid().'@test.fr',
        'password' => Hash::make('password'),
        'structure_id' => null,
        'is_platform_admin' => true,
    ]);

    test()->actingAs($admin);

    return $admin;
}

it('lists structures for a super_admin', function (): void {
    actingAsSuperAdmin();
    Structure::factory()->count(3)->create();

    $this->get('/admin/structures')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/structures/index')
            ->has('structures.data', 3),
        );
});

it('returns 404 to a tenant-scoped user (coordinateur) on the index', function (): void {
    actingAsRole('coordinateur');

    $this->get('/admin/structures')->assertNotFound();
});

it('returns 404 to a dirigeant on the index (cannot manage other tenants)', function (): void {
    actingAsRole('dirigeant');

    $this->get('/admin/structures')->assertNotFound();
});

it('redirects unauthenticated requests', function (): void {
    $this->get('/admin/structures')->assertRedirect('/login');
});

it('shows the create form for super_admin', function (): void {
    actingAsSuperAdmin();

    $this->get('/admin/structures/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/structures/create'));
});

it('provisions a structure + dirigeant via POST', function (): void {
    actingAsSuperAdmin();

    $this->post('/admin/structures', [
        'code' => 'HTTP-SAAD-1',
        'name' => 'HTTP SAAD',
        'type' => 'saad',
        'tier' => 'pro',
        'dirigeant' => [
            'first_name' => 'Marie',
            'last_name' => 'Durand',
            'email' => 'm.durand+http@test.fr',
        ],
    ])->assertRedirect();

    $structure = Structure::where('code', 'HTTP-SAAD-1')->firstOrFail();
    expect($structure->name)->toBe('HTTP SAAD');
    expect(User::where('email', 'm.durand+http@test.fr')->where('structure_id', $structure->id)->exists())
        ->toBeTrue();
});

it('rejects POST with missing required fields', function (): void {
    actingAsSuperAdmin();

    $response = $this->from('/admin/structures/create')->post('/admin/structures', [
        'code' => '',
        'name' => '',
    ]);

    $response->assertRedirect('/admin/structures/create');
    $response->assertSessionHasErrors(['code', 'name', 'type', 'dirigeant.first_name']);
});

it('rejects POST when a tenant-scoped user tries it', function (): void {
    actingAsRole('coordinateur');

    $this->post('/admin/structures', [
        'code' => 'BLOCKED', 'name' => 'X', 'type' => 'saad',
        'dirigeant' => ['first_name' => 'A', 'last_name' => 'B', 'email' => 'blocked@x.fr'],
    ])->assertNotFound();

    expect(Structure::where('code', 'BLOCKED')->exists())->toBeFalse();
});

it('shows a single structure to super_admin', function (): void {
    actingAsSuperAdmin();
    $structure = Structure::factory()->create();

    $this->get("/admin/structures/{$structure->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/structures/show')
            ->where('structure.id', $structure->id),
        );
});

it('updates a structure via PUT', function (): void {
    actingAsSuperAdmin();
    $structure = Structure::factory()->create(['name' => 'Original']);

    $this->put("/admin/structures/{$structure->id}", ['name' => 'Renamed'])
        ->assertRedirect();

    expect($structure->fresh()->name)->toBe('Renamed');
});

it('soft-deletes a structure via DELETE', function (): void {
    actingAsSuperAdmin();
    $structure = Structure::factory()->create();

    $this->delete("/admin/structures/{$structure->id}")->assertRedirect();

    expect(Structure::withTrashed()->find($structure->id)->trashed())->toBeTrue();
});

it('suspends and reactivates via dedicated endpoints', function (): void {
    actingAsSuperAdmin();
    $structure = Structure::factory()->create(['status' => 'active']);

    $this->post("/admin/structures/{$structure->id}/suspend")->assertRedirect();
    expect($structure->fresh()->status->value)->toBe('suspended');

    $this->post("/admin/structures/{$structure->id}/reactivate")->assertRedirect();
    expect($structure->fresh()->status->value)->toBe('active');
});
