<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\DemandeConge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\DemandeCongeController
 */
final class DemandeCongeControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $demandeConges = DemandeConge::factory()->count(3)->create();

        $response = $this->get(route('demande-conges.index'));
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\DemandeCongeController::class,
            'store',
            \App\Http\Requests\DemandeCongeControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $user = User::factory()->create();
        $type = fake()->randomElement(/** enum_attributes **/);
        $date_debut = Carbon::parse(fake()->date());
        $date_fin = Carbon::parse(fake()->date());
        $nombre_jours = fake()->numberBetween(-10000, 10000);

        $response = $this->post(route('demande-conges.store'), [
            'user_id' => $user->id,
            'type' => $type,
            'date_debut' => $date_debut,
            'date_fin' => $date_fin,
            'nombre_jours' => $nombre_jours,
        ]);

        $demandeConges = DemandeConge::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->where('date_debut', $date_debut)
            ->where('date_fin', $date_fin)
            ->where('nombre_jours', $nombre_jours)
            ->get();
        $this->assertCount(1, $demandeConges);
        $demandeConge = $demandeConges->first();

        $response->assertOk();
        $response->assertJson($demande_conge);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $demandeConge = DemandeConge::factory()->create();

        $response = $this->get(route('demande-conges.show', $demandeConge));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\DemandeCongeController::class,
            'update',
            \App\Http\Requests\DemandeCongeControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $demandeConge = DemandeConge::factory()->create();
        $user = User::factory()->create();
        $type = fake()->randomElement(/** enum_attributes **/);
        $date_debut = Carbon::parse(fake()->date());
        $date_fin = Carbon::parse(fake()->date());
        $nombre_jours = fake()->numberBetween(-10000, 10000);
        $statut = fake()->randomElement(/** enum_attributes **/);

        $response = $this->put(route('demande-conges.update', $demandeConge), [
            'user_id' => $user->id,
            'type' => $type,
            'date_debut' => $date_debut->toDateString(),
            'date_fin' => $date_fin->toDateString(),
            'nombre_jours' => $nombre_jours,
            'statut' => $statut,
        ]);

        $demandeConge->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($user->id, $demandeConge->user_id);
        $this->assertEquals($type, $demandeConge->type);
        $this->assertEquals($date_debut, $demandeConge->date_debut);
        $this->assertEquals($date_fin, $demandeConge->date_fin);
        $this->assertEquals($nombre_jours, $demandeConge->nombre_jours);
        $this->assertEquals($statut, $demandeConge->statut);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $demandeConge = DemandeConge::factory()->create();

        $response = $this->delete(route('demande-conges.destroy', $demandeConge));

        $response->assertNoContent();

        $this->assertModelMissing($demandeConge);
    }
}
