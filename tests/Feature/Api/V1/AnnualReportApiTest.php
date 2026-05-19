<?php

declare(strict_types=1);

use App\Enums\AnnualReportStatus;
use App\Jobs\GenerateAnnualReportJob;
use App\Models\AnnualReport;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    Queue::fake();
});

it('dirigeant can request an annual report and job is queued', function (): void {
    $dirigeant = actingAsApiRole('dirigeant');

    $this->postJson('/api/v1/annual-reports', ['year' => 2025])
        ->assertCreated()
        ->assertJsonPath('status', AnnualReportStatus::EnAttente->value);

    Queue::assertPushed(GenerateAnnualReportJob::class);
});

it('returns 422 when year is in the future', function (): void {
    actingAsApiRole('dirigeant');

    $this->postJson('/api/v1/annual-reports', ['year' => now()->year + 1])
        ->assertUnprocessable();
});

it('intervenant is forbidden from requesting a report', function (): void {
    actingAsApiRole('intervenant');

    $this->postJson('/api/v1/annual-reports', ['year' => 2025])
        ->assertForbidden();

    Queue::assertNothingPushed();
});

it('index lists reports for the structure only', function (): void {
    $dirigeant = actingAsApiRole('dirigeant');

    AnnualReport::factory()->create([
        'structure_id' => $dirigeant->structure->id,
        'year' => 2025,
        'status' => AnnualReportStatus::Genere->value,
        'requested_by_user_id' => $dirigeant->id,
    ]);

    $otherStructure = Structure::factory()->create();
    AnnualReport::factory()->create([
        'structure_id' => $otherStructure->id,
        'year' => 2025,
        'status' => AnnualReportStatus::Genere->value,
        'requested_by_user_id' => $dirigeant->id,
    ]);

    $this->getJson('/api/v1/annual-reports')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('unauthenticated request returns 401', function (): void {
    $this->getJson('/api/v1/annual-reports')->assertUnauthorized();
});
