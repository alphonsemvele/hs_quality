<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Facture;
use App\Models\Paiement;
use App\Models\RecuPar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\PaiementController
 */
final class PaiementControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $paiements = Paiement::factory()->count(3)->create();

        $response = $this->get(route('paiements.index'));
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\PaiementController::class,
            'store',
            \App\Http\Requests\PaiementControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $facture = Facture::factory()->create();
        $montant = fake()->randomFloat(/** decimal_attributes **/);
        $mode_paiement = fake()->randomElement(/** enum_attributes **/);
        $date_paiement = Carbon::parse(fake()->dateTime());

        $response = $this->post(route('paiements.store'), [
            'facture_id' => $facture->id,
            'montant' => $montant,
            'mode_paiement' => $mode_paiement,
            'date_paiement' => $date_paiement,
        ]);

        $paiements = Paiement::query()
            ->where('facture_id', $facture->id)
            ->where('montant', $montant)
            ->where('mode_paiement', $mode_paiement)
            ->where('date_paiement', $date_paiement)
            ->get();
        $this->assertCount(1, $paiements);
        $paiement = $paiements->first();

        $response->assertOk();
        $response->assertJson($paiement);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $paiement = Paiement::factory()->create();

        $response = $this->get(route('paiements.show', $paiement));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\PaiementController::class,
            'update',
            \App\Http\Requests\PaiementControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $paiement = Paiement::factory()->create();
        $numero = fake()->word();
        $facture = Facture::factory()->create();
        $date_paiement = Carbon::parse(fake()->dateTime());
        $montant = fake()->randomFloat(/** decimal_attributes **/);
        $mode_paiement = fake()->randomElement(/** enum_attributes **/);
        $recu_par = RecuPar::factory()->create();
        $statut = fake()->randomElement(/** enum_attributes **/);
        $recu_par = User::factory()->create();

        $response = $this->put(route('paiements.update', $paiement), [
            'numero' => $numero,
            'facture_id' => $facture->id,
            'date_paiement' => $date_paiement->toDateTimeString(),
            'montant' => $montant,
            'mode_paiement' => $mode_paiement,
            'recu_par' => $recu_par->id,
            'statut' => $statut,
            'recu_par_id' => $recu_par->id,
        ]);

        $paiement->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($numero, $paiement->numero);
        $this->assertEquals($facture->id, $paiement->facture_id);
        $this->assertEquals($date_paiement, $paiement->date_paiement);
        $this->assertEquals($montant, $paiement->montant);
        $this->assertEquals($mode_paiement, $paiement->mode_paiement);
        $this->assertEquals($recu_par->id, $paiement->recu_par);
        $this->assertEquals($statut, $paiement->statut);
        $this->assertEquals($recu_par->id, $paiement->recu_par_id);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $paiement = Paiement::factory()->create();

        $response = $this->delete(route('paiements.destroy', $paiement));

        $response->assertNoContent();

        $this->assertModelMissing($paiement);
    }
}
