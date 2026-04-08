<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\ExamenImagerie;
use App\Models\MedecinPrescripteur;
use App\Models\Patient;
use App\Models\TypeExamen;
use App\Models\TypeExamenImagerie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ExamenImagerieController
 */
final class ExamenImagerieControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $examenImageries = ExamenImagerie::factory()->count(3)->create();

        $response = $this->get(route('examen-imageries.index'));
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ExamenImagerieController::class,
            'store',
            \App\Http\Requests\ExamenImagerieControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $patient = Patient::factory()->create();
        $medecin_prescripteur = MedecinPrescripteur::factory()->create();
        $type_examen = TypeExamen::factory()->create();
        $date_prescription = Carbon::parse(fake()->dateTime());

        $response = $this->post(route('examen-imageries.store'), [
            'patient_id' => $patient->id,
            'medecin_prescripteur_id' => $medecin_prescripteur->id,
            'type_examen_id' => $type_examen->id,
            'date_prescription' => $date_prescription,
        ]);

        $examenImageries = ExamenImagerie::query()
            ->where('patient_id', $patient->id)
            ->where('medecin_prescripteur_id', $medecin_prescripteur->id)
            ->where('type_examen_id', $type_examen->id)
            ->where('date_prescription', $date_prescription)
            ->get();
        $this->assertCount(1, $examenImageries);
        $examenImagerie = $examenImageries->first();

        $response->assertOk();
        $response->assertJson($examen_imagerie);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $examenImagerie = ExamenImagerie::factory()->create();

        $response = $this->get(route('examen-imageries.show', $examenImagerie));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ExamenImagerieController::class,
            'update',
            \App\Http\Requests\ExamenImagerieControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $examenImagerie = ExamenImagerie::factory()->create();
        $numero = fake()->word();
        $patient = Patient::factory()->create();
        $medecin_prescripteur = MedecinPrescripteur::factory()->create();
        $type_examen = TypeExamen::factory()->create();
        $date_prescription = Carbon::parse(fake()->dateTime());
        $urgent = fake()->boolean();
        $statut = fake()->randomElement(/** enum_attributes **/);
        $type_examen_imagerie = TypeExamenImagerie::factory()->create();

        $response = $this->put(route('examen-imageries.update', $examenImagerie), [
            'numero' => $numero,
            'patient_id' => $patient->id,
            'medecin_prescripteur_id' => $medecin_prescripteur->id,
            'type_examen_id' => $type_examen->id,
            'date_prescription' => $date_prescription->toDateTimeString(),
            'urgent' => $urgent,
            'statut' => $statut,
            'type_examen_imagerie_id' => $type_examen_imagerie->id,
        ]);

        $examenImagerie->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($numero, $examenImagerie->numero);
        $this->assertEquals($patient->id, $examenImagerie->patient_id);
        $this->assertEquals($medecin_prescripteur->id, $examenImagerie->medecin_prescripteur_id);
        $this->assertEquals($type_examen->id, $examenImagerie->type_examen_id);
        $this->assertEquals($date_prescription, $examenImagerie->date_prescription);
        $this->assertEquals($urgent, $examenImagerie->urgent);
        $this->assertEquals($statut, $examenImagerie->statut);
        $this->assertEquals($type_examen_imagerie->id, $examenImagerie->type_examen_imagerie_id);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $examenImagerie = ExamenImagerie::factory()->create();

        $response = $this->delete(route('examen-imageries.destroy', $examenImagerie));

        $response->assertNoContent();

        $this->assertSoftDeleted($examenImagerie);
    }
}
