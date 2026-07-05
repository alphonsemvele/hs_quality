<?php

declare(strict_types=1);

use App\Enums\DataExportStatus;
use App\Jobs\Gdpr\GenerateDataExportJob;
use App\Models\DataExportRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('renders the GDPR page for an authenticated user', function (): void {
    actingAsRole('coordinateur');

    $response = $this->get('/dashboard/profile/gdpr');

    $response->assertSuccessful();
    $props = $response->viewData('page')['props'];
    expect($props)->toHaveKeys(['requests', 'pendingCount']);
    expect($props['requests'])->toBeArray();
});

it('redirects unauthenticated visitors to login', function (): void {
    $this->get('/dashboard/profile/gdpr')->assertRedirect('/login');
    $this->post('/dashboard/profile/gdpr/export')->assertRedirect('/login');
});

it('queues an export job when the user requests one', function (): void {
    Queue::fake();
    $user = actingAsRole('intervenant');

    $response = $this->post('/dashboard/profile/gdpr/export');

    $response->assertRedirect();
    $response->assertSessionHas('success');

    Queue::assertPushed(GenerateDataExportJob::class);
    expect(DataExportRequest::query()->where('user_id', $user->id)->where('status', DataExportStatus::Pending->value)->exists())->toBeTrue();
});

it('rejects a duplicate export request while one is still in flight', function (): void {
    Queue::fake();
    $user = actingAsRole('coordinateur');

    DataExportRequest::create([
        'user_id' => $user->id,
        'status' => DataExportStatus::Pending,
    ]);

    $response = $this->post('/dashboard/profile/gdpr/export');

    $response->assertRedirect();
    $response->assertSessionHas('info');
    Queue::assertNothingPushed();
});

it('refuses to let user A download user B\'s archive', function (): void {
    $userA = actingAsRole('coordinateur');
    $userB = User::factory()->forStructure($userA->structure)->create();

    $other = DataExportRequest::factory()->ready()->create([
        'structure_id' => $userA->structure->id,
        'user_id' => $userB->id,
    ]);

    $this->get('/dashboard/profile/gdpr/export/'.$other->id.'/download')->assertForbidden();
});

it('refuses to download an expired archive', function (): void {
    $user = actingAsRole('coordinateur');

    $export = DataExportRequest::factory()->ready()->create([
        'structure_id' => $user->structure->id,
        'user_id' => $user->id,
        'expires_at' => now()->subDay(),
    ]);

    $response = $this->get('/dashboard/profile/gdpr/export/'.$export->id.'/download');

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

it('requires explicit confirmation to request account deletion', function (): void {
    actingAsRole('coordinateur');

    $this->post('/dashboard/profile/gdpr/delete-account', [])->assertSessionHasErrors('confirm');
    $this->post('/dashboard/profile/gdpr/delete-account', ['confirm' => true])
        ->assertRedirect()
        ->assertSessionHas('success');
});
