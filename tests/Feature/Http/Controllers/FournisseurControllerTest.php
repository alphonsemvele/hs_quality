<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Fournisseur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\FournisseurController
 */
final class FournisseurControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_responds_with(): void
    {
        $fournisseurs = Fournisseur::factory()->count(3)->create();

        $response = $this->get(route('fournisseurs.index'));

        $response->assertOk();
        $response->assertJson($fournisseurs);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\FournisseurController::class,
            'store',
            \App\Http\Requests\FournisseurControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $code = fake()->word();
        $nom = fake()->word();
        $type = fake()->randomElement(/** enum_attributes **/);

        $response = $this->post(route('fournisseurs.store'), [
            'code' => $code,
            'nom' => $nom,
            'type' => $type,
        ]);

        $fournisseurs = Fournisseur::query()
            ->where('code', $code)
            ->where('nom', $nom)
            ->where('type', $type)
            ->get();
        $this->assertCount(1, $fournisseurs);
        $fournisseur = $fournisseurs->first();

        $response->assertOk();
        $response->assertJson($fournisseur);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $fournisseur = Fournisseur::factory()->create();

        $response = $this->get(route('fournisseurs.show', $fournisseur));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\FournisseurController::class,
            'update',
            \App\Http\Requests\FournisseurControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $fournisseur = Fournisseur::factory()->create();
        $code = fake()->word();
        $nom = fake()->word();
        $type = fake()->randomElement(/** enum_attributes **/);
        $pays = fake()->word();
        $delai_livraison_jours = fake()->numberBetween(-10000, 10000);
        $actif = fake()->boolean();

        $response = $this->put(route('fournisseurs.update', $fournisseur), [
            'code' => $code,
            'nom' => $nom,
            'type' => $type,
            'pays' => $pays,
            'delai_livraison_jours' => $delai_livraison_jours,
            'actif' => $actif,
        ]);

        $fournisseur->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($code, $fournisseur->code);
        $this->assertEquals($nom, $fournisseur->nom);
        $this->assertEquals($type, $fournisseur->type);
        $this->assertEquals($pays, $fournisseur->pays);
        $this->assertEquals($delai_livraison_jours, $fournisseur->delai_livraison_jours);
        $this->assertEquals($actif, $fournisseur->actif);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $fournisseur = Fournisseur::factory()->create();

        $response = $this->delete(route('fournisseurs.destroy', $fournisseur));

        $response->assertNoContent();

        $this->assertSoftDeleted($fournisseur);
    }
}
