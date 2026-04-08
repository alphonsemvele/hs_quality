<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Pointage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\PointageController
 */
final class PointageControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $pointages = Pointage::factory()->count(3)->create();

        $response = $this->get(route('pointages.index'));
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\PointageController::class,
            'store',
            \App\Http\Requests\PointageControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $user = User::factory()->create();
        $date = Carbon::parse(fake()->date());

        $response = $this->post(route('pointages.store'), [
            'user_id' => $user->id,
            'date' => $date,
        ]);

        $pointages = Pointage::query()
            ->where('user_id', $user->id)
            ->where('date', $date)
            ->get();
        $this->assertCount(1, $pointages);
        $pointage = $pointages->first();

        $response->assertOk();
        $response->assertJson($pointage);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $pointage = Pointage::factory()->create();

        $response = $this->get(route('pointages.show', $pointage));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\PointageController::class,
            'update',
            \App\Http\Requests\PointageControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $pointage = Pointage::factory()->create();
        $user = User::factory()->create();
        $date = Carbon::parse(fake()->date());
        $type = fake()->randomElement(/** enum_attributes **/);
        $source = fake()->randomElement(/** enum_attributes **/);
        $statut = fake()->randomElement(/** enum_attributes **/);

        $response = $this->put(route('pointages.update', $pointage), [
            'user_id' => $user->id,
            'date' => $date->toDateString(),
            'type' => $type,
            'source' => $source,
            'statut' => $statut,
        ]);

        $pointage->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($user->id, $pointage->user_id);
        $this->assertEquals($date, $pointage->date);
        $this->assertEquals($type, $pointage->type);
        $this->assertEquals($source, $pointage->source);
        $this->assertEquals($statut, $pointage->statut);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $pointage = Pointage::factory()->create();

        $response = $this->delete(route('pointages.destroy', $pointage));

        $response->assertNoContent();

        $this->assertModelMissing($pointage);
    }
}
