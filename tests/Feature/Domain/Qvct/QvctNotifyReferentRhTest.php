<?php

declare(strict_types=1);

use App\Jobs\NotifyReferentRhJob;
use App\Models\QvctQuestionnaire;
use App\Models\QvctResponse;
use App\Models\Structure;
use App\Models\User;
use App\Services\QvctService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Bus;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->launcher = User::factory()->forStructure($this->structure)->create();
    $this->questionnaire = QvctQuestionnaire::factory()->forStructure($this->structure)->create();
});

it('dispatches NotifyReferentRhJob once per emitted weak signal on close', function (): void {
    Bus::fake([NotifyReferentRhJob::class]);

    $service = app(QvctService::class);
    $campaign = $service->launchCampaign($this->questionnaire, [
        'opens_at' => now()->subDay()->toDateString(),
        'closes_at' => now()->addDay()->toDateString(),
    ], $this->launcher);

    // Seed 3 negative responses (mean 1.0 on morale + charge → both signals fire).
    QvctResponse::factory()->forCampaign($campaign)->count(3)->create([
        'answers' => ['morale' => 1, 'charge' => 1, 'relations' => 5, 'isolement' => 5],
        'team_tag' => null,
    ]);

    $service->closeCampaign($campaign);

    // Two distinct signal types fired (baisse_morale + surcharge).
    Bus::assertDispatched(NotifyReferentRhJob::class, 2);
});

it('does not dispatch NotifyReferentRhJob when no signals fire', function (): void {
    Bus::fake([NotifyReferentRhJob::class]);

    $service = app(QvctService::class);
    $campaign = $service->launchCampaign($this->questionnaire, [
        'opens_at' => now()->subDay()->toDateString(),
        'closes_at' => now()->addDay()->toDateString(),
    ], $this->launcher);

    // All-positive responses → no signals.
    QvctResponse::factory()->forCampaign($campaign)->count(3)->create([
        'answers' => ['morale' => 5, 'charge' => 5, 'relations' => 5, 'isolement' => 5],
        'team_tag' => null,
    ]);

    $service->closeCampaign($campaign);

    Bus::assertNotDispatched(NotifyReferentRhJob::class);
});
