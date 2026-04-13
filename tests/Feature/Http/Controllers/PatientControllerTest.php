<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\PatientController
 */
final class PatientControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $patients = Patient::factory()->count(3)->create();

        $response = $this->get(route('patients.index'));
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\PatientController::class,
            'store',
            \App\Http\Requests\PatientControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $nom = fake()->word();
        $prenom = fake()->word();
        $date_naissance = Carbon::parse(fake()->date());
        $sexe = fake()->randomElement(/** enum_attributes **/);

        $response = $this->post(route('patients.store'), [
            'nom' => $nom,
            'prenom' => $prenom,
            'date_naissance' => $date_naissance,
            'sexe' => $sexe,
        ]);

        $patients = Patient::query()
            ->where('nom', $nom)
            ->where('prenom', $prenom)
            ->where('date_naissance', $date_naissance)
            ->where('sexe', $sexe)
            ->get();
        $this->assertCount(1, $patients);
        $patient = $patients->first();

        $response->assertOk();
        $response->assertJson($patient);
    }


    #[Test]
    public function show_responds_with(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->get(route('patients.show', $patient));

        $response->assertOk();
        $response->assertJson($patient);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\PatientController::class,
            'update',
            \App\Http\Requests\PatientControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_responds_with(): void
    {
        $patient = Patient::factory()->create();
        $nom = fake()->word();
        $prenom = fake()->word();
        $date_naissance = Carbon::parse(fake()->date());
        $sexe = fake()->randomElement(/** enum_attributes **/);

        $response = $this->put(route('patients.update', $patient), [
            'nom' => $nom,
            'prenom' => $prenom,
            'date_naissance' => $date_naissance,
            'sexe' => $sexe,
        ]);

        $patient->refresh();

        $response->assertOk();
        $response->assertJson($patient);

        $this->assertEquals($nom, $patient->nom);
        $this->assertEquals($prenom, $patient->prenom);
        $this->assertEquals($date_naissance, $patient->date_naissance);
        $this->assertEquals($sexe, $patient->sexe);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->delete(route('patients.destroy', $patient));

        $response->assertNoContent();

        $this->assertSoftDeleted($patient);
    }
}
