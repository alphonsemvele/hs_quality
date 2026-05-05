<?php

declare(strict_types=1);

use App\Enums\PacActionStatus;
use App\Enums\PacStatus;
use App\Models\Pac;
use App\Models\PacAction;
use App\Models\Structure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);
});

// ── Pac status transitions ──────────────────────────────────────────────

it('logs Pac status transitions with from + to values', function (): void {
    $pac = Pac::factory()->forStructure($this->structure)->create([
        'status' => PacStatus::Draft,
    ]);

    Log::shouldReceive('info')
        ->once()
        ->withArgs(function (string $message, array $context) use ($pac): bool {
            return $message === 'PAC status transition'
                && $context['pac_id'] === $pac->id
                && $context['from'] === 'draft'
                && $context['to'] === 'active';
        });

    $pac->update(['status' => PacStatus::Active]);
});

it('does not log Pac save when status did not change', function (): void {
    $pac = Pac::factory()->forStructure($this->structure)->create([
        'status' => PacStatus::Draft,
    ]);

    Log::shouldReceive('info')->never();

    // Touch a non-status column.
    $pac->update(['title' => 'Updated title']);
});

it('logs Pac deletion', function (): void {
    $pac = Pac::factory()->forStructure($this->structure)->create([
        'status' => PacStatus::Active,
    ]);

    Log::shouldReceive('info')
        ->once()
        ->withArgs(function (string $message, array $context) use ($pac): bool {
            return $message === 'PAC deleted'
                && $context['pac_id'] === $pac->id
                && $context['status_at_delete'] === 'active';
        });

    $pac->delete();
});

// ── PacAction status transitions ────────────────────────────────────────

it('logs PacAction status transitions with from + to values', function (): void {
    $pac = Pac::factory()->forStructure($this->structure)->create();
    $action = PacAction::factory()->forPac($pac)->create([
        'status' => PacActionStatus::Pending,
    ]);

    Log::shouldReceive('info')
        ->once()
        ->withArgs(function (string $message, array $context) use ($action): bool {
            return $message === 'PAC action status transition'
                && $context['action_id'] === $action->id
                && $context['pac_id'] === $action->pac_id
                && $context['from'] === 'pending'
                && $context['to'] === 'in_progress';
        });

    $action->update(['status' => PacActionStatus::InProgress]);
});

it('does not log PacAction save when status did not change', function (): void {
    $pac = Pac::factory()->forStructure($this->structure)->create();
    $action = PacAction::factory()->forPac($pac)->create([
        'status' => PacActionStatus::InProgress,
    ]);

    Log::shouldReceive('info')->never();

    $action->update(['responsible_user_id' => null]);
});
