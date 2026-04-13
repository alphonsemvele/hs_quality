<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Consultation;
use App\Models\Medecin;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ConsultationController
 */
final class ConsultationControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $consultations = Consultation::factory()->count(3)->create();

        $response = $this->get(route('consultations.index'));
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ConsultationController::class,
            'store',
            \App\Http\Requests\ConsultationControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $patient = Patient::factory()->create();
        $medecin = Medecin::factory()->create();
        $motif = fake()->text();
        $date_consultation = Carbon::parse(fake()->dateTime());

        $response = $this->post(route('consultations.store'), [
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'motif' => $motif,
            'date_consultation' => $date_consultation,
        ]);

        $consultations = Consultation::query()
            ->where('patient_id', $patient->id)
            ->where('medecin_id', $medecin->id)
            ->where('motif', $motif)
            ->where('date_consultation', $date_consultation)
            ->get();
        $this->assertCount(1, $consultations);
        $consultation = $consultations->first();

        $response->assertOk();
        $response->assertJson($consultation);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $consultation = Consultation::factory()->create();

        $response = $this->get(route('consultations.show', $consultation));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ConsultationController::class,
            'update',
            \App\Http\Requests\ConsultationControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $consultation = Consultation::factory()->create();
        $numero = fake()->word();
        $patient = Patient::factory()->create();
        $medecin = Medecin::factory()->create();
        $date_consultation = Carbon::parse(fake()->dateTime());
        $motif = fake()->text();
        $statut = fake()->randomElement(/** enum_attributes **/);

        $response = $this->put(route('consultations.update', $consultation), [
            'numero' => $numero,
            'patient_id' => $patient->id,
            'medecin_id' => $medecin->id,
            'date_consultation' => $date_consultation->toDateTimeString(),
            'motif' => $motif,
            'statut' => $statut,
        ]);

        $consultation->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($numero, $consultation->numero);
        $this->assertEquals($patient->id, $consultation->patient_id);
        $this->assertEquals($medecin->id, $consultation->medecin_id);
        $this->assertEquals($date_consultation, $consultation->date_consultation);
        $this->assertEquals($motif, $consultation->motif);
        $this->assertEquals($statut, $consultation->statut);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $consultation = Consultation::factory()->create();

        $response = $this->delete(route('consultations.destroy', $consultation));

        $response->assertNoContent();

        $this->assertSoftDeleted($consultation);
    }
}
