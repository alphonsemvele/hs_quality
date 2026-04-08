<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Admission;
use App\Models\Medecin;
use App\Models\Patient;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\AdmissionController
 */
final class AdmissionControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $admissions = Admission::factory()->count(3)->create();

        $response = $this->get(route('admissions.index'));
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\AdmissionController::class,
            'store',
            \App\Http\Requests\AdmissionControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $patient = Patient::factory()->create();
        $service = Service::factory()->create();
        $medecin = Medecin::factory()->create();
        $motif = fake()->text();
        $date_admission = Carbon::parse(fake()->dateTime());

        $response = $this->post(route('admissions.store'), [
            'patient_id' => $patient->id,
            'service_id' => $service->id,
            'medecin_id' => $medecin->id,
            'motif' => $motif,
            'date_admission' => $date_admission,
        ]);

        $admissions = Admission::query()
            ->where('patient_id', $patient->id)
            ->where('service_id', $service->id)
            ->where('medecin_id', $medecin->id)
            ->where('motif', $motif)
            ->where('date_admission', $date_admission)
            ->get();
        $this->assertCount(1, $admissions);
        $admission = $admissions->first();

        $response->assertOk();
        $response->assertJson($admission);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $admission = Admission::factory()->create();

        $response = $this->get(route('admissions.show', $admission));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\AdmissionController::class,
            'update',
            \App\Http\Requests\AdmissionControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $admission = Admission::factory()->create();
        $numero = fake()->word();
        $patient = Patient::factory()->create();
        $service = Service::factory()->create();
        $medecin = Medecin::factory()->create();
        $type = fake()->randomElement(/** enum_attributes **/);
        $motif = fake()->text();
        $date_admission = Carbon::parse(fake()->dateTime());
        $statut = fake()->randomElement(/** enum_attributes **/);

        $response = $this->put(route('admissions.update', $admission), [
            'numero' => $numero,
            'patient_id' => $patient->id,
            'service_id' => $service->id,
            'medecin_id' => $medecin->id,
            'type' => $type,
            'motif' => $motif,
            'date_admission' => $date_admission->toDateTimeString(),
            'statut' => $statut,
        ]);

        $admission->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($numero, $admission->numero);
        $this->assertEquals($patient->id, $admission->patient_id);
        $this->assertEquals($service->id, $admission->service_id);
        $this->assertEquals($medecin->id, $admission->medecin_id);
        $this->assertEquals($type, $admission->type);
        $this->assertEquals($motif, $admission->motif);
        $this->assertEquals($date_admission, $admission->date_admission);
        $this->assertEquals($statut, $admission->statut);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $admission = Admission::factory()->create();

        $response = $this->delete(route('admissions.destroy', $admission));

        $response->assertNoContent();

        $this->assertSoftDeleted($admission);
    }
}
