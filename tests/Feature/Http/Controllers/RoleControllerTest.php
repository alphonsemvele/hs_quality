<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\RoleController
 */
final class RoleControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_responds_with(): void
    {
        $roles = Role::factory()->count(3)->create();

        $response = $this->get(route('roles.index'));

        $response->assertOk();
        $response->assertJson($roles);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\RoleController::class,
            'store',
            \App\Http\Requests\RoleControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $nom = fake()->word();

        $response = $this->post(route('roles.store'), [
            'nom' => $nom,
        ]);

        $roles = Role::query()
            ->where('nom', $nom)
            ->get();
        $this->assertCount(1, $roles);
        $role = $roles->first();

        $response->assertOk();
        $response->assertJson($role);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $role = Role::factory()->create();

        $response = $this->get(route('roles.show', $role));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\RoleController::class,
            'update',
            \App\Http\Requests\RoleControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $role = Role::factory()->create();
        $nom = fake()->word();

        $response = $this->put(route('roles.update', $role), [
            'nom' => $nom,
        ]);

        $role->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($nom, $role->nom);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $role = Role::factory()->create();

        $response = $this->delete(route('roles.destroy', $role));

        $response->assertNoContent();

        $this->assertModelMissing($role);
    }
}
