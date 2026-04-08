<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Medicament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\MedicamentController
 */
final class MedicamentControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $medicaments = Medicament::factory()->count(3)->create();

        $response = $this->get(route('medicaments.index'));
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\MedicamentController::class,
            'store',
            \App\Http\Requests\MedicamentControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $code = fake()->word();
        $nom = fake()->word();
        $forme = fake()->randomElement(/** enum_attributes **/);
        $categorie = fake()->randomElement(/** enum_attributes **/);
        $prix_vente = fake()->randomFloat(/** decimal_attributes **/);

        $response = $this->post(route('medicaments.store'), [
            'code' => $code,
            'nom' => $nom,
            'forme' => $forme,
            'categorie' => $categorie,
            'prix_vente' => $prix_vente,
        ]);

        $medicaments = Medicament::query()
            ->where('code', $code)
            ->where('nom', $nom)
            ->where('forme', $forme)
            ->where('categorie', $categorie)
            ->where('prix_vente', $prix_vente)
            ->get();
        $this->assertCount(1, $medicaments);
        $medicament = $medicaments->first();

        $response->assertOk();
        $response->assertJson($medicament);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $medicament = Medicament::factory()->create();

        $response = $this->get(route('medicaments.show', $medicament));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\MedicamentController::class,
            'update',
            \App\Http\Requests\MedicamentControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $medicament = Medicament::factory()->create();
        $code = fake()->word();
        $nom = fake()->word();
        $forme = fake()->randomElement(/** enum_attributes **/);
        $categorie = fake()->randomElement(/** enum_attributes **/);
        $voie_administration = fake()->randomElement(/** enum_attributes **/);
        $stock_actuel = fake()->numberBetween(-10000, 10000);
        $stock_minimum = fake()->numberBetween(-10000, 10000);
        $stock_maximum = fake()->numberBetween(-10000, 10000);
        $prix_achat = fake()->randomFloat(/** decimal_attributes **/);
        $prix_vente = fake()->randomFloat(/** decimal_attributes **/);
        $tva = fake()->randomFloat(/** decimal_attributes **/);
        $ordonnance_obligatoire = fake()->boolean();
        $actif = fake()->boolean();

        $response = $this->put(route('medicaments.update', $medicament), [
            'code' => $code,
            'nom' => $nom,
            'forme' => $forme,
            'categorie' => $categorie,
            'voie_administration' => $voie_administration,
            'stock_actuel' => $stock_actuel,
            'stock_minimum' => $stock_minimum,
            'stock_maximum' => $stock_maximum,
            'prix_achat' => $prix_achat,
            'prix_vente' => $prix_vente,
            'tva' => $tva,
            'ordonnance_obligatoire' => $ordonnance_obligatoire,
            'actif' => $actif,
        ]);

        $medicament->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($code, $medicament->code);
        $this->assertEquals($nom, $medicament->nom);
        $this->assertEquals($forme, $medicament->forme);
        $this->assertEquals($categorie, $medicament->categorie);
        $this->assertEquals($voie_administration, $medicament->voie_administration);
        $this->assertEquals($stock_actuel, $medicament->stock_actuel);
        $this->assertEquals($stock_minimum, $medicament->stock_minimum);
        $this->assertEquals($stock_maximum, $medicament->stock_maximum);
        $this->assertEquals($prix_achat, $medicament->prix_achat);
        $this->assertEquals($prix_vente, $medicament->prix_vente);
        $this->assertEquals($tva, $medicament->tva);
        $this->assertEquals($ordonnance_obligatoire, $medicament->ordonnance_obligatoire);
        $this->assertEquals($actif, $medicament->actif);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $medicament = Medicament::factory()->create();

        $response = $this->delete(route('medicaments.destroy', $medicament));

        $response->assertNoContent();

        $this->assertSoftDeleted($medicament);
    }
}
