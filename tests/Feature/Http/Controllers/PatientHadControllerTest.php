<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\MedecinReferent;
use App\Models\Patient;
use App\Models\PatientHad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\PatientHadController
 */
final class PatientHadControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $patientHads = PatientHad::factory()->count(3)->create();

        $response = $this->get(route('patient-hads.index'));
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\PatientHadController::class,
            'store',
            \App\Http\Requests\PatientHadControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $patient = Patient::factory()->create();
        $medecin_referent = MedecinReferent::factory()->create();
        $date_inclusion = Carbon::parse(fake()->date());
        $motif_inclusion = fake()->text();
        $adresse_domicile = fake()->text();

        $response = $this->post(route('patient-hads.store'), [
            'patient_id' => $patient->id,
            'medecin_referent_id' => $medecin_referent->id,
            'date_inclusion' => $date_inclusion,
            'motif_inclusion' => $motif_inclusion,
            'adresse_domicile' => $adresse_domicile,
        ]);

        $patientHads = PatientHad::query()
            ->where('patient_id', $patient->id)
            ->where('medecin_referent_id', $medecin_referent->id)
            ->where('date_inclusion', $date_inclusion)
            ->where('motif_inclusion', $motif_inclusion)
            ->where('adresse_domicile', $adresse_domicile)
            ->get();
        $this->assertCount(1, $patientHads);
        $patientHad = $patientHads->first();

        $response->assertOk();
        $response->assertJson($patient_had);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $patientHad = PatientHad::factory()->create();

        $response = $this->get(route('patient-hads.show', $patientHad));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\PatientHadController::class,
            'update',
            \App\Http\Requests\PatientHadControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $patientHad = PatientHad::factory()->create();
        $patient = Patient::factory()->create();
        $medecin_referent = MedecinReferent::factory()->create();
        $date_inclusion = Carbon::parse(fake()->date());
        $motif_inclusion = fake()->text();
        $adresse_domicile = fake()->text();
        $frequence_visites = fake()->randomElement(/** enum_attributes **/);
        $statut = fake()->randomElement(/** enum_attributes **/);

        $response = $this->put(route('patient-hads.update', $patientHad), [
            'patient_id' => $patient->id,
            'medecin_referent_id' => $medecin_referent->id,
            'date_inclusion' => $date_inclusion->toDateString(),
            'motif_inclusion' => $motif_inclusion,
            'adresse_domicile' => $adresse_domicile,
            'frequence_visites' => $frequence_visites,
            'statut' => $statut,
        ]);

        $patientHad->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($patient->id, $patientHad->patient_id);
        $this->assertEquals($medecin_referent->id, $patientHad->medecin_referent_id);
        $this->assertEquals($date_inclusion, $patientHad->date_inclusion);
        $this->assertEquals($motif_inclusion, $patientHad->motif_inclusion);
        $this->assertEquals($adresse_domicile, $patientHad->adresse_domicile);
        $this->assertEquals($frequence_visites, $patientHad->frequence_visites);
        $this->assertEquals($statut, $patientHad->statut);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $patientHad = PatientHad::factory()->create();

        $response = $this->delete(route('patient-hads.destroy', $patientHad));

        $response->assertNoContent();

        $this->assertSoftDeleted($patientHad);
    }
}
