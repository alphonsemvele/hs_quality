<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('marks a single notification as read', function () {
    $user = actingAsRole('coordinateur');

    $id = (string) Str::uuid();
    DatabaseNotification::query()->create([
        'id' => $id,
        'type' => 'App\\Notifications\\Demo',
        'notifiable_type' => $user::class,
        'notifiable_id' => $user->getKey(),
        'data' => ['title' => 'Test', 'message' => null],
    ]);

    expect($user->unreadNotifications()->count())->toBe(1);

    $response = $this->post("/notifications/{$id}/read");

    $response->assertRedirect();
    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

it('marks all notifications as read', function () {
    $user = actingAsRole('rh');

    foreach (range(1, 3) as $i) {
        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\Demo',
            'notifiable_type' => $user::class,
            'notifiable_id' => $user->getKey(),
            'data' => ['title' => "Test {$i}"],
        ]);
    }

    expect($user->unreadNotifications()->count())->toBe(3);

    $response = $this->post('/notifications/read-all');

    $response->assertRedirect();
    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

it('does not allow reading another user\'s notification', function () {
    $alice = actingAsRole('coordinateur');
    $bob = User::factory()->forStructure($alice->structure)->state(['type' => 'intervenant'])->create();

    $bobNotifId = (string) Str::uuid();
    DatabaseNotification::query()->create([
        'id' => $bobNotifId,
        'type' => 'App\\Notifications\\Demo',
        'notifiable_type' => $bob::class,
        'notifiable_id' => $bob->getKey(),
        'data' => ['title' => 'Bob only'],
    ]);

    $this->post("/notifications/{$bobNotifId}/read")->assertRedirect();

    // Alice's call should not mark Bob's notification as read — the controller
    // only operates on $user->notifications() (current user's own collection).
    expect(DatabaseNotification::query()->find($bobNotifId)?->read_at)->toBeNull();
});

it('rejects unauthenticated calls', function () {
    $this->post('/notifications/anything/read')->assertRedirect('/login');
    $this->post('/notifications/read-all')->assertRedirect('/login');
});

function seedNotification(User $user, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();
    DatabaseNotification::query()->create(array_merge([
        'id' => $id,
        'type' => $overrides['type'] ?? 'App\\Notifications\\Demo',
        'notifiable_type' => $user::class,
        'notifiable_id' => $user->getKey(),
        'data' => $overrides['data'] ?? ['title' => 'Sample', 'message' => null, 'level' => 'info'],
        'read_at' => $overrides['read_at'] ?? null,
        'created_at' => $overrides['created_at'] ?? now(),
    ], array_diff_key($overrides, array_flip(['id', 'type', 'data', 'read_at', 'created_at']))));

    return $id;
}

it('renders the paginated notifications index for the current user', function () {
    $user = actingAsRole('coordinateur');

    foreach (range(1, 25) as $i) {
        seedNotification($user, ['data' => ['title' => "Notif {$i}", 'level' => 'info']]);
    }

    $response = $this->get('/notifications');

    $response->assertSuccessful();
    $props = $response->viewData('page')['props'];
    expect($props['items'])->toHaveCount(20);
    expect($props['pagination']['total'])->toBe(25);
    expect($props['pagination']['last_page'])->toBe(2);
    expect($props['unread_total'])->toBe(25);
});

it('filters the index by unread status', function () {
    $user = actingAsRole('coordinateur');
    seedNotification($user, ['read_at' => now(), 'data' => ['title' => 'Already read', 'level' => 'info']]);
    seedNotification($user, ['data' => ['title' => 'Still unread', 'level' => 'info']]);

    $response = $this->get('/notifications?status=unread');

    $props = $response->viewData('page')['props'];
    expect($props['items'])->toHaveCount(1);
    expect($props['items'][0]['title'])->toBe('Still unread');
});

it('filters the index by level', function () {
    $user = actingAsRole('coordinateur');
    seedNotification($user, ['data' => ['title' => 'A info', 'level' => 'info']]);
    seedNotification($user, ['data' => ['title' => 'A danger', 'level' => 'danger']]);

    $response = $this->get('/notifications?level=danger');

    $props = $response->viewData('page')['props'];
    expect(collect($props['items'])->pluck('title')->all())->toEqual(['A danger']);
});

it('lets a user delete one of their own notifications', function () {
    $user = actingAsRole('coordinateur');
    $id = seedNotification($user);

    $this->delete("/notifications/{$id}")
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(DatabaseNotification::query()->find($id))->toBeNull();
});

it('refuses to delete another user\'s notification', function () {
    $alice = actingAsRole('coordinateur');
    $bob = User::factory()->forStructure($alice->structure)->state(['type' => 'intervenant'])->create();
    $bobId = seedNotification($bob);

    $this->delete("/notifications/{$bobId}")->assertNotFound();
    expect(DatabaseNotification::query()->find($bobId))->not->toBeNull();
});

it('shares notifications payload through Inertia', function () {
    $user = actingAsRole('dirigeant');

    DatabaseNotification::query()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\Demo',
        'notifiable_type' => $user::class,
        'notifiable_id' => $user->getKey(),
        'data' => ['title' => 'Hello', 'message' => 'Just a test'],
    ]);

    $response = $this->get('/dashboard');

    $response->assertSuccessful();
    $payload = $response->viewData('page')['props']['notifications'] ?? null;
    expect($payload)->not->toBeNull()
        ->and($payload['unread_count'])->toBe(1)
        ->and($payload['items'])->toHaveCount(1)
        ->and($payload['items'][0]['title'])->toBe('Hello');
});
