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
