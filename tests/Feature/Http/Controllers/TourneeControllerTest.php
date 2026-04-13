<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Soignant;
use App\Models\Tournee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\TourneeController
 */
final class TourneeControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $tournees = Tournee::factory()->count(3)->create();

        $response = $this->get(route('tournees.index'));
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\TourneeController::class,
            'store',
            \App\Http\Requests\TourneeControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $date = Carbon::parse(fake()->date());
        $soignant = Soignant::factory()->create();

        $response = $this->post(route('tournees.store'), [
            'date' => $date,
            'soignant_id' => $soignant->id,
        ]);

        $tournees = Tournee::query()
            ->where('date', $date)
            ->where('soignant_id', $soignant->id)
            ->get();
        $this->assertCount(1, $tournees);
        $tournee = $tournees->first();

        $response->assertOk();
        $response->assertJson($tournee);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $tournee = Tournee::factory()->create();

        $response = $this->get(route('tournees.show', $tournee));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\TourneeController::class,
            'update',
            \App\Http\Requests\TourneeControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $tournee = Tournee::factory()->create();
        $date = Carbon::parse(fake()->date());
        $soignant = Soignant::factory()->create();
        $statut = fake()->randomElement(/** enum_attributes **/);

        $response = $this->put(route('tournees.update', $tournee), [
            'date' => $date->toDateString(),
            'soignant_id' => $soignant->id,
            'statut' => $statut,
        ]);

        $tournee->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($date, $tournee->date);
        $this->assertEquals($soignant->id, $tournee->soignant_id);
        $this->assertEquals($statut, $tournee->statut);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $tournee = Tournee::factory()->create();

        $response = $this->delete(route('tournees.destroy', $tournee));

        $response->assertNoContent();

        $this->assertModelMissing($tournee);
    }
}
