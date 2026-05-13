<?php

use App\Models\Beneficiary;
use App\Models\BeneficiarySatisfactionRating;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('renders the satisfaction page with history and stats', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();

    BeneficiarySatisfactionRating::factory()
        ->forBeneficiary($beneficiary)
        ->withScore(5)
        ->create(['rated_at' => '2026-04-01']);
    BeneficiarySatisfactionRating::factory()
        ->forBeneficiary($beneficiary)
        ->withScore(4)
        ->create(['rated_at' => '2026-04-30']);

    $this->get("/beneficiaries/{$beneficiary->id}/satisfaction")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/beneficiaries/satisfaction')
            ->has('ratings', 2)
            ->where('stats.count', 2)
            ->where('stats.average', 4.5)
        );
});

it('records a new satisfaction rating', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();

    $this->post("/beneficiaries/{$beneficiary->id}/satisfaction", [
        'score' => 4,
        'comment' => 'Très content du suivi',
        'rated_at' => now()->toDateString(),
    ])->assertRedirect("/beneficiaries/{$beneficiary->id}/satisfaction");

    $rating = BeneficiarySatisfactionRating::query()
        ->where('beneficiary_id', $beneficiary->id)
        ->first();

    expect($rating)->not->toBeNull();
    expect($rating->score)->toBe(4);
    expect($rating->comment)->toBe('Très content du suivi');
    expect($rating->rated_by)->toBe($user->id);
    expect($rating->structure_id)->toBe($beneficiary->structure_id);
});

it('rejects a score outside 1..5', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();

    $this->post("/beneficiaries/{$beneficiary->id}/satisfaction", [
        'score' => 7,
        'rated_at' => now()->toDateString(),
    ])->assertSessionHasErrors('score');

    expect(BeneficiarySatisfactionRating::query()->count())->toBe(0);
});

it('rejects a future rated_at date', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();

    $this->post("/beneficiaries/{$beneficiary->id}/satisfaction", [
        'score' => 4,
        'rated_at' => now()->addWeek()->toDateString(),
    ])->assertSessionHasErrors('rated_at');
});

it('forbids unauthenticated access', function () {
    $beneficiary = Beneficiary::factory()->forStructure(Structure::factory()->create())->create();

    $this->get("/beneficiaries/{$beneficiary->id}/satisfaction")
        ->assertRedirect('/login');

    $this->post("/beneficiaries/{$beneficiary->id}/satisfaction", ['score' => 3, 'rated_at' => now()->toDateString()])
        ->assertRedirect('/login');
});

it('forbids intervenant from recording a rating (no beneficiaries.update permission)', function () {
    $user = actingAsRole('intervenant');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();

    $this->post("/beneficiaries/{$beneficiary->id}/satisfaction", [
        'score' => 5,
        'rated_at' => now()->toDateString(),
    ])->assertForbidden();
});
