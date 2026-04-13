<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Medicament;
use App\Models\MouvementStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\MouvementStockController
 */
final class MouvementStockControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $mouvementStocks = MouvementStock::factory()->count(3)->create();

        $response = $this->get(route('mouvement-stocks.index'));
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\MouvementStockController::class,
            'store',
            \App\Http\Requests\MouvementStockControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $medicament = Medicament::factory()->create();
        $type = fake()->randomElement(/** enum_attributes **/);
        $quantite = fake()->numberBetween(-10000, 10000);
        $user = User::factory()->create();

        $response = $this->post(route('mouvement-stocks.store'), [
            'medicament_id' => $medicament->id,
            'type' => $type,
            'quantite' => $quantite,
            'user_id' => $user->id,
        ]);

        $mouvementStocks = MouvementStock::query()
            ->where('medicament_id', $medicament->id)
            ->where('type', $type)
            ->where('quantite', $quantite)
            ->where('user_id', $user->id)
            ->get();
        $this->assertCount(1, $mouvementStocks);
        $mouvementStock = $mouvementStocks->first();

        $response->assertOk();
        $response->assertJson($mouvement_stock);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $mouvementStock = MouvementStock::factory()->create();

        $response = $this->get(route('mouvement-stocks.show', $mouvementStock));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\MouvementStockController::class,
            'update',
            \App\Http\Requests\MouvementStockControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $mouvementStock = MouvementStock::factory()->create();
        $medicament = Medicament::factory()->create();
        $type = fake()->randomElement(/** enum_attributes **/);
        $quantite = fake()->numberBetween(-10000, 10000);
        $stock_avant = fake()->numberBetween(-10000, 10000);
        $stock_apres = fake()->numberBetween(-10000, 10000);
        $user = User::factory()->create();

        $response = $this->put(route('mouvement-stocks.update', $mouvementStock), [
            'medicament_id' => $medicament->id,
            'type' => $type,
            'quantite' => $quantite,
            'stock_avant' => $stock_avant,
            'stock_apres' => $stock_apres,
            'user_id' => $user->id,
        ]);

        $mouvementStock->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($medicament->id, $mouvementStock->medicament_id);
        $this->assertEquals($type, $mouvementStock->type);
        $this->assertEquals($quantite, $mouvementStock->quantite);
        $this->assertEquals($stock_avant, $mouvementStock->stock_avant);
        $this->assertEquals($stock_apres, $mouvementStock->stock_apres);
        $this->assertEquals($user->id, $mouvementStock->user_id);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $mouvementStock = MouvementStock::factory()->create();

        $response = $this->delete(route('mouvement-stocks.destroy', $mouvementStock));

        $response->assertNoContent();

        $this->assertModelMissing($mouvementStock);
    }
}
