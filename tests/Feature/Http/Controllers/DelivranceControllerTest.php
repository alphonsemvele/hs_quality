<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Delivrance;
use App\Models\Pharmacien;
use App\Models\Prescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\DelivranceController
 */
final class DelivranceControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $delivrances = Delivrance::factory()->count(3)->create();

        $response = $this->get(route('delivrances.index'));
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\DelivranceController::class,
            'store',
            \App\Http\Requests\DelivranceControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $prescription = Prescription::factory()->create();
        $pharmacien = Pharmacien::factory()->create();
        $date_delivrance = Carbon::parse(fake()->dateTime());

        $response = $this->post(route('delivrances.store'), [
            'prescription_id' => $prescription->id,
            'pharmacien_id' => $pharmacien->id,
            'date_delivrance' => $date_delivrance,
        ]);

        $delivrances = Delivrance::query()
            ->where('prescription_id', $prescription->id)
            ->where('pharmacien_id', $pharmacien->id)
            ->where('date_delivrance', $date_delivrance)
            ->get();
        $this->assertCount(1, $delivrances);
        $delivrance = $delivrances->first();

        $response->assertOk();
        $response->assertJson($delivrance);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $delivrance = Delivrance::factory()->create();

        $response = $this->get(route('delivrances.show', $delivrance));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\DelivranceController::class,
            'update',
            \App\Http\Requests\DelivranceControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $delivrance = Delivrance::factory()->create();
        $numero = fake()->word();
        $prescription = Prescription::factory()->create();
        $pharmacien = Pharmacien::factory()->create();
        $date_delivrance = Carbon::parse(fake()->dateTime());
        $montant_total = fake()->randomFloat(/** decimal_attributes **/);
        $montant_paye = fake()->randomFloat(/** decimal_attributes **/);
        $statut = fake()->randomElement(/** enum_attributes **/);

        $response = $this->put(route('delivrances.update', $delivrance), [
            'numero' => $numero,
            'prescription_id' => $prescription->id,
            'pharmacien_id' => $pharmacien->id,
            'date_delivrance' => $date_delivrance->toDateTimeString(),
            'montant_total' => $montant_total,
            'montant_paye' => $montant_paye,
            'statut' => $statut,
        ]);

        $delivrance->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($numero, $delivrance->numero);
        $this->assertEquals($prescription->id, $delivrance->prescription_id);
        $this->assertEquals($pharmacien->id, $delivrance->pharmacien_id);
        $this->assertEquals($date_delivrance, $delivrance->date_delivrance);
        $this->assertEquals($montant_total, $delivrance->montant_total);
        $this->assertEquals($montant_paye, $delivrance->montant_paye);
        $this->assertEquals($statut, $delivrance->statut);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $delivrance = Delivrance::factory()->create();

        $response = $this->delete(route('delivrances.destroy', $delivrance));

        $response->assertNoContent();

        $this->assertModelMissing($delivrance);
    }
}
