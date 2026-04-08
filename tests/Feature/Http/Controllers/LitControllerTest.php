<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Lit;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\LitController
 */
final class LitControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $lits = Lit::factory()->count(3)->create();

        $response = $this->get(route('lits.index'));
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\LitController::class,
            'store',
            \App\Http\Requests\LitControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $numero = fake()->word();
        $service = Service::factory()->create();
        $type = fake()->randomElement(/** enum_attributes **/);

        $response = $this->post(route('lits.store'), [
            'numero' => $numero,
            'service_id' => $service->id,
            'type' => $type,
        ]);

        $lits = Lit::query()
            ->where('numero', $numero)
            ->where('service_id', $service->id)
            ->where('type', $type)
            ->get();
        $this->assertCount(1, $lits);
        $lit = $lits->first();

        $response->assertOk();
        $response->assertJson($lit);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $lit = Lit::factory()->create();

        $response = $this->get(route('lits.show', $lit));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\LitController::class,
            'update',
            \App\Http\Requests\LitControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $lit = Lit::factory()->create();
        $numero = fake()->word();
        $service = Service::factory()->create();
        $type = fake()->randomElement(/** enum_attributes **/);
        $statut = fake()->randomElement(/** enum_attributes **/);
        $tarif_journalier = fake()->randomFloat(/** decimal_attributes **/);

        $response = $this->put(route('lits.update', $lit), [
            'numero' => $numero,
            'service_id' => $service->id,
            'type' => $type,
            'statut' => $statut,
            'tarif_journalier' => $tarif_journalier,
        ]);

        $lit->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($numero, $lit->numero);
        $this->assertEquals($service->id, $lit->service_id);
        $this->assertEquals($type, $lit->type);
        $this->assertEquals($statut, $lit->statut);
        $this->assertEquals($tarif_journalier, $lit->tarif_journalier);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $lit = Lit::factory()->create();

        $response = $this->delete(route('lits.destroy', $lit));

        $response->assertNoContent();

        $this->assertSoftDeleted($lit);
    }
}
