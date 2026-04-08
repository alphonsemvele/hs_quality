<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\AnalyseLaboratoire;
use App\Models\MedecinPrescripteur;
use App\Models\Patient;
use App\Models\TypeAnalyse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\AnalyseLaboratoireController
 */
final class AnalyseLaboratoireControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $analyseLaboratoires = AnalyseLaboratoire::factory()->count(3)->create();

        $response = $this->get(route('analyse-laboratoires.index'));
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\AnalyseLaboratoireController::class,
            'store',
            \App\Http\Requests\AnalyseLaboratoireControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $patient = Patient::factory()->create();
        $medecin_prescripteur = MedecinPrescripteur::factory()->create();
        $type_analyse = TypeAnalyse::factory()->create();
        $date_prescription = Carbon::parse(fake()->dateTime());

        $response = $this->post(route('analyse-laboratoires.store'), [
            'patient_id' => $patient->id,
            'medecin_prescripteur_id' => $medecin_prescripteur->id,
            'type_analyse_id' => $type_analyse->id,
            'date_prescription' => $date_prescription,
        ]);

        $analyseLaboratoires = AnalyseLaboratoire::query()
            ->where('patient_id', $patient->id)
            ->where('medecin_prescripteur_id', $medecin_prescripteur->id)
            ->where('type_analyse_id', $type_analyse->id)
            ->where('date_prescription', $date_prescription)
            ->get();
        $this->assertCount(1, $analyseLaboratoires);
        $analyseLaboratoire = $analyseLaboratoires->first();

        $response->assertOk();
        $response->assertJson($analyse_laboratoire);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $analyseLaboratoire = AnalyseLaboratoire::factory()->create();

        $response = $this->get(route('analyse-laboratoires.show', $analyseLaboratoire));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\AnalyseLaboratoireController::class,
            'update',
            \App\Http\Requests\AnalyseLaboratoireControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $analyseLaboratoire = AnalyseLaboratoire::factory()->create();
        $numero = fake()->word();
        $patient = Patient::factory()->create();
        $medecin_prescripteur = MedecinPrescripteur::factory()->create();
        $type_analyse = TypeAnalyse::factory()->create();
        $date_prescription = Carbon::parse(fake()->dateTime());
        $urgent = fake()->boolean();
        $statut = fake()->randomElement(/** enum_attributes **/);

        $response = $this->put(route('analyse-laboratoires.update', $analyseLaboratoire), [
            'numero' => $numero,
            'patient_id' => $patient->id,
            'medecin_prescripteur_id' => $medecin_prescripteur->id,
            'type_analyse_id' => $type_analyse->id,
            'date_prescription' => $date_prescription->toDateTimeString(),
            'urgent' => $urgent,
            'statut' => $statut,
        ]);

        $analyseLaboratoire->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($numero, $analyseLaboratoire->numero);
        $this->assertEquals($patient->id, $analyseLaboratoire->patient_id);
        $this->assertEquals($medecin_prescripteur->id, $analyseLaboratoire->medecin_prescripteur_id);
        $this->assertEquals($type_analyse->id, $analyseLaboratoire->type_analyse_id);
        $this->assertEquals($date_prescription, $analyseLaboratoire->date_prescription);
        $this->assertEquals($urgent, $analyseLaboratoire->urgent);
        $this->assertEquals($statut, $analyseLaboratoire->statut);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $analyseLaboratoire = AnalyseLaboratoire::factory()->create();

        $response = $this->delete(route('analyse-laboratoires.destroy', $analyseLaboratoire));

        $response->assertNoContent();

        $this->assertSoftDeleted($analyseLaboratoire);
    }
}
