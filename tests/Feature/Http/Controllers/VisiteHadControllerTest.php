<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\PatientHad;
use App\Models\Tournee;
use App\Models\VisiteHad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\VisiteHadController
 */
final class VisiteHadControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $visiteHads = VisiteHad::factory()->count(3)->create();

        $response = $this->get(route('visite-hads.index'));
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\VisiteHadController::class,
            'store',
            \App\Http\Requests\VisiteHadControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $tournee = Tournee::factory()->create();
        $patient_had = PatientHad::factory()->create();

        $response = $this->post(route('visite-hads.store'), [
            'tournee_id' => $tournee->id,
            'patient_had_id' => $patient_had->id,
        ]);

        $visiteHads = VisiteHad::query()
            ->where('tournee_id', $tournee->id)
            ->where('patient_had_id', $patient_had->id)
            ->get();
        $this->assertCount(1, $visiteHads);
        $visiteHad = $visiteHads->first();

        $response->assertOk();
        $response->assertJson($visite_had);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $visiteHad = VisiteHad::factory()->create();

        $response = $this->get(route('visite-hads.show', $visiteHad));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\VisiteHadController::class,
            'update',
            \App\Http\Requests\VisiteHadControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $visiteHad = VisiteHad::factory()->create();
        $tournee = Tournee::factory()->create();
        $patient_had = PatientHad::factory()->create();
        $ordre = fake()->numberBetween(-10000, 10000);
        $statut = fake()->randomElement(/** enum_attributes **/);

        $response = $this->put(route('visite-hads.update', $visiteHad), [
            'tournee_id' => $tournee->id,
            'patient_had_id' => $patient_had->id,
            'ordre' => $ordre,
            'statut' => $statut,
        ]);

        $visiteHad->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($tournee->id, $visiteHad->tournee_id);
        $this->assertEquals($patient_had->id, $visiteHad->patient_had_id);
        $this->assertEquals($ordre, $visiteHad->ordre);
        $this->assertEquals($statut, $visiteHad->statut);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $visiteHad = VisiteHad::factory()->create();

        $response = $this->delete(route('visite-hads.destroy', $visiteHad));

        $response->assertNoContent();

        $this->assertModelMissing($visiteHad);
    }
}
