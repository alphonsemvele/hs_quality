<?php

declare(strict_types=1);

use App\Models\Habilitation;
use App\Models\Structure;
use App\Models\User;
use App\Services\HabilitationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(HabilitationService::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->user = User::factory()->forStructure($this->structure)->create();
});

it('records a habilitation with structure inherited from the user', function (): void {
    $hab = $this->service->record(
        $this->user,
        type: 'DEAS',
        referenceNumber: 'RNCP-12345',
        validFrom: Carbon::parse('2020-06-15'),
    );

    expect($hab)->toBeInstanceOf(Habilitation::class)
        ->and($hab->structure_id)->toBe($this->structure->id)
        ->and($hab->user_id)->toBe($this->user->id)
        ->and($hab->type)->toBe('DEAS')
        ->and($hab->reference_number)->toBe('RNCP-12345')
        ->and($hab->valid_from?->toDateString())->toBe('2020-06-15');
});

it('renews a habilitation and updates valid_until', function (): void {
    $hab = Habilitation::factory()->forUser($this->user)->create([
        'valid_until' => '2024-12-31',
    ]);

    $renewed = $this->service->renew(
        $hab,
        newValidUntil: Carbon::parse('2027-12-31'),
    );

    expect($renewed->valid_until?->toDateString())->toBe('2027-12-31');
});

it('refuses to renew a soft-deleted habilitation', function (): void {
    $hab = Habilitation::factory()->forUser($this->user)->create();
    $hab->delete();

    expect(fn () => $this->service->renew($hab, Carbon::parse('2030-01-01')))
        ->toThrow(HttpException::class);
});

it('expire soft-deletes the habilitation', function (): void {
    $hab = Habilitation::factory()->forUser($this->user)->create();

    $this->service->expire($hab);

    expect(Habilitation::find($hab->id))->toBeNull();
    expect(Habilitation::withTrashed()->find($hab->id))->not->toBeNull();
});
