<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ServiceController
 */
final class ServiceControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_responds_with(): void
    {
        $services = Service::factory()->count(3)->create();

        $response = $this->get(route('services.index'));

        $response->assertOk();
        $response->assertJson($services);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ServiceController::class,
            'store',
            \App\Http\Requests\ServiceControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $code = fake()->word();
        $nom = fake()->word();

        $response = $this->post(route('services.store'), [
            'code' => $code,
            'nom' => $nom,
        ]);

        $services = Service::query()
            ->where('code', $code)
            ->where('nom', $nom)
            ->get();
        $this->assertCount(1, $services);
        $service = $services->first();

        $response->assertOk();
        $response->assertJson($service);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $service = Service::factory()->create();

        $response = $this->get(route('services.show', $service));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ServiceController::class,
            'update',
            \App\Http\Requests\ServiceControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $service = Service::factory()->create();
        $code = fake()->word();
        $nom = fake()->word();
        $capacite_lits = fake()->numberBetween(-10000, 10000);
        $actif = fake()->boolean();

        $response = $this->put(route('services.update', $service), [
            'code' => $code,
            'nom' => $nom,
            'capacite_lits' => $capacite_lits,
            'actif' => $actif,
        ]);

        $service->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($code, $service->code);
        $this->assertEquals($nom, $service->nom);
        $this->assertEquals($capacite_lits, $service->capacite_lits);
        $this->assertEquals($actif, $service->actif);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $service = Service::factory()->create();

        $response = $this->delete(route('services.destroy', $service));

        $response->assertNoContent();

        $this->assertSoftDeleted($service);
    }
}
