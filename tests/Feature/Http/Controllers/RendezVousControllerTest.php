<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Medecin;
use App\Models\Patient;
use App\Models\RendezVou;
use App\Models\RendezVous;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\RendezVousController
 */
final class RendezVousControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $rendezVous = RendezVous::factory()->count(3)->create();

        $response = $this->get(route('rendez-vous.index'));
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\RendezVousController::class,
            'store',
            \App\Http\Requests\RendezVousControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $patient = Patient::factory()->create();
        $medecin = Medecin::factory()->create();
        $date_heure = Carbon::parse(fake()->dateTime());
        $type = fake()->randomElement(/** enum_attributes **/);

        $response = $this->post(route('rendez-vous.store'), [
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'date_heure' => $date_heure,
            'type' => $type,
        ]);

        $rendezVous = RendezVous::query()
            ->where('patient_id', $patient->id)
            ->where('medecin_id', $medecin->id)
            ->where('date_heure', $date_heure)
            ->where('type', $type)
            ->get();
        $this->assertCount(1, $rendezVous);
        $rendezVou = $rendezVous->first();

        $response->assertOk();
        $response->assertJson($rendez_vous);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $rendezVou = RendezVous::factory()->create();

        $response = $this->get(route('rendez-vous.show', $rendezVou));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\RendezVousController::class,
            'update',
            \App\Http\Requests\RendezVousControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $rendezVou = RendezVous::factory()->create();
        $patient = Patient::factory()->create();
        $medecin = Medecin::factory()->create();
        $date_heure = Carbon::parse(fake()->dateTime());
        $duree_minutes = fake()->numberBetween(-10000, 10000);
        $type = fake()->randomElement(/** enum_attributes **/);
        $statut = fake()->randomElement(/** enum_attributes **/);

        $response = $this->put(route('rendez-vous.update', $rendezVou), [
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'date_heure' => $date_heure->toDateTimeString(),
            'duree_minutes' => $duree_minutes,
            'type' => $type,
            'statut' => $statut,
        ]);

        $rendezVou->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($patient->id, $rendezVou->patient_id);
        $this->assertEquals($medecin->id, $rendezVou->medecin_id);
        $this->assertEquals($date_heure, $rendezVou->date_heure);
        $this->assertEquals($duree_minutes, $rendezVou->duree_minutes);
        $this->assertEquals($type, $rendezVou->type);
        $this->assertEquals($statut, $rendezVou->statut);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $rendezVou = RendezVous::factory()->create();
        $rendezVou = RendezVou::factory()->create();

        $response = $this->delete(route('rendez-vous.destroy', $rendezVou));

        $response->assertNoContent();

        $this->assertModelMissing($rendezVou);
    }
}
