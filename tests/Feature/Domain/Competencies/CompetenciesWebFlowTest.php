<?php

declare(strict_types=1);

use App\Models\Certification;
use App\Models\Habilitation;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('records a habilitation for a user via the web', function (): void {
    $admin = actingAsRole('rh');
    $target = User::factory()->forStructure($admin->structure)->create();

    $this->post('/formations/habilitations', [
        'user_id' => $target->id,
        'type' => 'Aide à la toilette',
        'valid_from' => '2026-01-01',
        'valid_until' => '2028-01-01',
    ])->assertRedirect();

    expect(Habilitation::query()->where('user_id', $target->id)->count())->toBe(1);
});

it('records a certification for a user via the web', function (): void {
    $admin = actingAsRole('rh');
    $target = User::factory()->forStructure($admin->structure)->create();

    $this->post('/formations/certifications', [
        'user_id' => $target->id,
        'type' => 'PSC1',
        'issued_on' => '2026-01-01',
        'expires_at' => '2028-01-01',
    ])->assertRedirect();

    expect(Certification::query()->where('user_id', $target->id)->count())->toBe(1);
});

it('lists the current user own habilitations + certifications on /formations/competencies/mine', function (): void {
    $me = actingAsRole('intervenant');
    Habilitation::factory()->create([
        'structure_id' => $me->structure_id,
        'user_id' => $me->id,
        'type' => 'Aide à la prise médicamenteuse',
    ]);
    Certification::factory()->create([
        'structure_id' => $me->structure_id,
        'user_id' => $me->id,
        'type' => 'PSC1',
    ]);
    // A coworker's records — must NOT leak into "my" view.
    $other = User::factory()->forStructure($me->structure)->create();
    Habilitation::factory()->create([
        'structure_id' => $me->structure_id,
        'user_id' => $other->id,
    ]);

    $this->get('/formations/competencies/mine')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('dashboard/formations/competencies/mine')
            ->has('habilitations', 1)
            ->has('certifications', 1));
});

it('surfaces real expiring alerts on the formations index', function (): void {
    $admin = actingAsRole('dirigeant');
    $someone = User::factory()->forStructure($admin->structure)->create([
        'first_name' => 'Tina',
        'last_name' => 'Tester',
    ]);
    Certification::factory()->create([
        'structure_id' => $admin->structure_id,
        'user_id' => $someone->id,
        'type' => 'PSC1 — expirant',
        'issued_on' => now()->subYears(2)->toDateString(),
        'expires_at' => now()->addDays(30)->toDateString(),
    ]);

    $this->get('/formations')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('dashboard/formations/index')
            ->where('expiringAlerts.0.intitule', 'PSC1 — expirant')
            ->where('expiringAlerts.0.intervenant', 'Tina Tester'));
});
