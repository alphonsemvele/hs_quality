<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Medecin;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\PrescriptionController
 */
final class PrescriptionControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $prescriptions = Prescription::factory()->count(3)->create();

        $response = $this->get(route('prescriptions.index'));
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\PrescriptionController::class,
            'store',
            \App\Http\Requests\PrescriptionControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $patient = Patient::factory()->create();
        $medecin = Medecin::factory()->create();
        $date_prescription = Carbon::parse(fake()->dateTime());

        $response = $this->post(route('prescriptions.store'), [
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'date_prescription' => $date_prescription,
        ]);

        $prescriptions = Prescription::query()
            ->where('patient_id', $patient->id)
            ->where('medecin_id', $medecin->id)
            ->where('date_prescription', $date_prescription)
            ->get();
        $this->assertCount(1, $prescriptions);
        $prescription = $prescriptions->first();

        $response->assertOk();
        $response->assertJson($prescription);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $prescription = Prescription::factory()->create();

        $response = $this->get(route('prescriptions.show', $prescription));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\PrescriptionController::class,
            'update',
            \App\Http\Requests\PrescriptionControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $prescription = Prescription::factory()->create();
        $numero = fake()->word();
        $patient = Patient::factory()->create();
        $medecin = Medecin::factory()->create();
        $date_prescription = Carbon::parse(fake()->dateTime());
        $statut = fake()->randomElement(/** enum_attributes **/);

        $response = $this->put(route('prescriptions.update', $prescription), [
            'numero' => $numero,
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'date_prescription' => $date_prescription->toDateTimeString(),
            'statut' => $statut,
        ]);

        $prescription->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($numero, $prescription->numero);
        $this->assertEquals($patient->id, $prescription->patient_id);
        $this->assertEquals($medecin->id, $prescription->medecin_id);
        $this->assertEquals($date_prescription, $prescription->date_prescription);
        $this->assertEquals($statut, $prescription->statut);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $prescription = Prescription::factory()->create();

        $response = $this->delete(route('prescriptions.destroy', $prescription));

        $response->assertNoContent();

        $this->assertSoftDeleted($prescription);
    }
}
