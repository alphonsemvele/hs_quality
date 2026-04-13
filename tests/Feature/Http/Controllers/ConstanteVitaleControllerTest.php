<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\ConstanteVitale;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ConstanteVitaleController
 */
final class ConstanteVitaleControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $constanteVitales = ConstanteVitale::factory()->count(3)->create();

        $response = $this->get(route('constante-vitales.index'));
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ConstanteVitaleController::class,
            'store',
            \App\Http\Requests\ConstanteVitaleControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $patient = Patient::factory()->create();
        $user = User::factory()->create();
        $date_mesure = Carbon::parse(fake()->dateTime());

        $response = $this->post(route('constante-vitales.store'), [
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'date_mesure' => $date_mesure,
        ]);

        $constanteVitales = ConstanteVitale::query()
            ->where('patient_id', $patient->id)
            ->where('user_id', $user->id)
            ->where('date_mesure', $date_mesure)
            ->get();
        $this->assertCount(1, $constanteVitales);
        $constanteVitale = $constanteVitales->first();

        $response->assertOk();
        $response->assertJson($constante_vitale);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $constanteVitale = ConstanteVitale::factory()->create();

        $response = $this->get(route('constante-vitales.show', $constanteVitale));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ConstanteVitaleController::class,
            'update',
            \App\Http\Requests\ConstanteVitaleControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $constanteVitale = ConstanteVitale::factory()->create();
        $patient = Patient::factory()->create();
        $user = User::factory()->create();
        $date_mesure = Carbon::parse(fake()->dateTime());

        $response = $this->put(route('constante-vitales.update', $constanteVitale), [
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'date_mesure' => $date_mesure->toDateTimeString(),
        ]);

        $constanteVitale->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($patient->id, $constanteVitale->patient_id);
        $this->assertEquals($user->id, $constanteVitale->user_id);
        $this->assertEquals($date_mesure, $constanteVitale->date_mesure);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $constanteVitale = ConstanteVitale::factory()->create();

        $response = $this->delete(route('constante-vitales.destroy', $constanteVitale));

        $response->assertNoContent();

        $this->assertModelMissing($constanteVitale);
    }
}
