<?php

declare(strict_types=1);

use App\Enums\DataExportStatus;
use App\Models\DataExportRequest;
use App\Models\Structure;
use App\Models\User;
use App\Notifications\Gdpr\DataExportReadyNotification;
use App\Services\Gdpr\DataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('s3');
    Notification::fake();
    $this->service = app(DataExportService::class);
});

it('marks the request as ready and writes the archive to storage', function (): void {
    $structure = Structure::factory()->create();
    $user = User::factory()->forStructure($structure)->create();
    $request = DataExportRequest::factory()->create([
        'structure_id' => $structure->id,
        'user_id' => $user->id,
    ]);

    $this->service->process($request);

    $request->refresh();

    expect($request->status)->toBe(DataExportStatus::Ready)
        ->and($request->archive_path)->toContain('gdpr-exports/')
        ->and($request->archive_size_bytes)->toBeGreaterThan(0)
        ->and($request->expires_at)->not->toBeNull()
        ->and($request->processed_at)->not->toBeNull();

    Storage::disk('s3')->assertExists($request->archive_path);
    Notification::assertSentTo($user, DataExportReadyNotification::class);
});

it('records a failure reason when archive write fails', function (): void {
    $structure = Structure::factory()->create();
    $user = User::factory()->forStructure($structure)->create();
    $request = DataExportRequest::factory()->create([
        'structure_id' => $structure->id,
        'user_id' => $user->id,
    ]);

    Storage::shouldReceive('disk')
        ->andThrow(new RuntimeException('S3 down'));

    $this->service->process($request);

    $request->refresh();

    expect($request->status)->toBe(DataExportStatus::Failed)
        ->and($request->failure_reason)->toContain('S3 down');

    Notification::assertNothingSent();
});

it('returns null when the request is not downloadable', function (): void {
    $request = DataExportRequest::factory()->create();

    expect($this->service->downloadUrl($request))->toBeNull();
});
