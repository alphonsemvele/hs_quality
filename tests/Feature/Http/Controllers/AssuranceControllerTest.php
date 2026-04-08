<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Assurance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\AssuranceController
 */
final class AssuranceControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_responds_with(): void
    {
        $assurances = Assurance::factory()->count(3)->create();

        $response = $this->get(route('assurances.index'));

        $response->assertOk();
        $response->assertJson($assurances);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\AssuranceController::class,
            'store',
            \App\Http\Requests\AssuranceControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $code = fake()->word();
        $nom = fake()->word();
        $type = fake()->randomElement(/** enum_attributes **/);

        $response = $this->post(route('assurances.store'), [
            'code' => $code,
            'nom' => $nom,
            'type' => $type,
        ]);

        $assurances = Assurance::query()
            ->where('code', $code)
            ->where('nom', $nom)
            ->where('type', $type)
            ->get();
        $this->assertCount(1, $assurances);
        $assurance = $assurances->first();

        $response->assertOk();
        $response->assertJson($assurance);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $assurance = Assurance::factory()->create();

        $response = $this->get(route('assurances.show', $assurance));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\AssuranceController::class,
            'update',
            \App\Http\Requests\AssuranceControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $assurance = Assurance::factory()->create();
        $code = fake()->word();
        $nom = fake()->word();
        $type = fake()->randomElement(/** enum_attributes **/);
        $taux_couverture = fake()->randomFloat(/** decimal_attributes **/);
        $delai_paiement = fake()->numberBetween(-10000, 10000);
        $actif = fake()->boolean();

        $response = $this->put(route('assurances.update', $assurance), [
            'code' => $code,
            'nom' => $nom,
            'type' => $type,
            'taux_couverture' => $taux_couverture,
            'delai_paiement' => $delai_paiement,
            'actif' => $actif,
        ]);

        $assurance->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($code, $assurance->code);
        $this->assertEquals($nom, $assurance->nom);
        $this->assertEquals($type, $assurance->type);
        $this->assertEquals($taux_couverture, $assurance->taux_couverture);
        $this->assertEquals($delai_paiement, $assurance->delai_paiement);
        $this->assertEquals($actif, $assurance->actif);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $assurance = Assurance::factory()->create();

        $response = $this->delete(route('assurances.destroy', $assurance));

        $response->assertNoContent();

        $this->assertSoftDeleted($assurance);
    }
}
