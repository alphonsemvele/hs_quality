<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Facture;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\FactureController
 */
final class FactureControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $factures = Facture::factory()->count(3)->create();

        $response = $this->get(route('factures.index'));
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\FactureController::class,
            'store',
            \App\Http\Requests\FactureControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $patient = Patient::factory()->create();
        $date_facture = Carbon::parse(fake()->date());

        $response = $this->post(route('factures.store'), [
            'patient_id' => $patient->id,
            'date_facture' => $date_facture,
        ]);

        $factures = Facture::query()
            ->where('patient_id', $patient->id)
            ->where('date_facture', $date_facture)
            ->get();
        $this->assertCount(1, $factures);
        $facture = $factures->first();

        $response->assertOk();
        $response->assertJson($facture);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $facture = Facture::factory()->create();

        $response = $this->get(route('factures.show', $facture));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\FactureController::class,
            'update',
            \App\Http\Requests\FactureControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $facture = Facture::factory()->create();
        $numero = fake()->word();
        $patient = Patient::factory()->create();
        $date_facture = Carbon::parse(fake()->date());
        $montant_brut = fake()->randomFloat(/** decimal_attributes **/);
        $remise = fake()->randomFloat(/** decimal_attributes **/);
        $montant_assurance = fake()->randomFloat(/** decimal_attributes **/);
        $montant_patient = fake()->randomFloat(/** decimal_attributes **/);
        $montant_total = fake()->randomFloat(/** decimal_attributes **/);
        $montant_paye = fake()->randomFloat(/** decimal_attributes **/);
        $tva = fake()->randomFloat(/** decimal_attributes **/);
        $statut = fake()->randomElement(/** enum_attributes **/);

        $response = $this->put(route('factures.update', $facture), [
            'numero' => $numero,
            'patient_id' => $patient->id,
            'date_facture' => $date_facture->toDateString(),
            'montant_brut' => $montant_brut,
            'remise' => $remise,
            'montant_assurance' => $montant_assurance,
            'montant_patient' => $montant_patient,
            'montant_total' => $montant_total,
            'montant_paye' => $montant_paye,
            'tva' => $tva,
            'statut' => $statut,
        ]);

        $facture->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($numero, $facture->numero);
        $this->assertEquals($patient->id, $facture->patient_id);
        $this->assertEquals($date_facture, $facture->date_facture);
        $this->assertEquals($montant_brut, $facture->montant_brut);
        $this->assertEquals($remise, $facture->remise);
        $this->assertEquals($montant_assurance, $facture->montant_assurance);
        $this->assertEquals($montant_patient, $facture->montant_patient);
        $this->assertEquals($montant_total, $facture->montant_total);
        $this->assertEquals($montant_paye, $facture->montant_paye);
        $this->assertEquals($tva, $facture->tva);
        $this->assertEquals($statut, $facture->statut);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $facture = Facture::factory()->create();

        $response = $this->delete(route('factures.destroy', $facture));

        $response->assertNoContent();

        $this->assertSoftDeleted($facture);
    }
}
