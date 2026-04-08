<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\UserController
 */
final class UserControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $users = User::factory()->count(3)->create();

        $response = $this->get(route('users.index'));
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\UserController::class,
            'store',
            \App\Http\Requests\UserControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $nom = fake()->word();
        $prenom = fake()->word();
        $email = fake()->safeEmail();
        $password = fake()->password();

        $response = $this->post(route('users.store'), [
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'password' => $password,
        ]);

        $users = User::query()
            ->where('nom', $nom)
            ->where('prenom', $prenom)
            ->where('email', $email)
            ->where('password', $password)
            ->get();
        $this->assertCount(1, $users);
        $user = $users->first();

        $response->assertOk();
        $response->assertJson($user);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $user = User::factory()->create();

        $response = $this->get(route('users.show', $user));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\UserController::class,
            'update',
            \App\Http\Requests\UserControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $user = User::factory()->create();
        $nom = fake()->word();
        $prenom = fake()->word();
        $email = fake()->safeEmail();
        $password = fake()->password();
        $statut = fake()->randomElement(/** enum_attributes **/);

        $response = $this->put(route('users.update', $user), [
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'password' => $password,
            'statut' => $statut,
        ]);

        $user->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($nom, $user->nom);
        $this->assertEquals($prenom, $user->prenom);
        $this->assertEquals($email, $user->email);
        $this->assertEquals($password, $user->password);
        $this->assertEquals($statut, $user->statut);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $user = User::factory()->create();

        $response = $this->delete(route('users.destroy', $user));

        $response->assertNoContent();

        $this->assertSoftDeleted($user);
    }
}
