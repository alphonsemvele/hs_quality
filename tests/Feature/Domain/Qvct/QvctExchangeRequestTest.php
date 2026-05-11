<?php

declare(strict_types=1);

use App\Enums\QvctExchangeAddresseeRole;
use App\Enums\QvctExchangeStatus;
use App\Models\QvctExchangeRequest;
use App\Models\Structure;
use App\Models\User;
use App\Services\ExchangeRequestService;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);
});

function makeQvctExchangeUser(string $role, Structure $structure): User
{
    $user = User::factory()->forStructure($structure)->state(['type' => $role])->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($structure->getKey());
    $user->assignRole($role);

    return $user;
}

// ── Lifecycle (service unit-style) ─────────────────────────────────────────────

it('intervenant can create an exchange request to RH', function (): void {
    $intervenant = makeQvctExchangeUser('intervenant', $this->structure);
    $service = app(ExchangeRequestService::class);

    $request = $service->create(
        $intervenant,
        QvctExchangeAddresseeRole::Rh,
        'Besoin d\'échanger sur ma situation.',
    );

    expect($request->status)->toBe(QvctExchangeStatus::Pending)
        ->and($request->requester_id)->toBe($intervenant->id)
        ->and($request->addressee_role)->toBe(QvctExchangeAddresseeRole::Rh);
});

it('rh can accept then schedule then close a request', function (): void {
    $intervenant = makeQvctExchangeUser('intervenant', $this->structure);
    $rh = makeQvctExchangeUser('rh', $this->structure);
    $service = app(ExchangeRequestService::class);

    $req = QvctExchangeRequest::factory()->fromUser($intervenant)->toRh()->create();

    $accepted = $service->accept($req, $rh);
    expect($accepted->status)->toBe(QvctExchangeStatus::Accepted)
        ->and($accepted->accepted_by)->toBe($rh->id);

    $scheduled = $service->schedule($accepted, now()->addDay());
    expect($scheduled->status)->toBe(QvctExchangeStatus::Scheduled);

    $closed = $service->close($scheduled, 'Meeting held.');
    expect($closed->status)->toBe(QvctExchangeStatus::Closed)
        ->and($closed->closed_reason)->toBe('Meeting held.');
});

it('rejects scheduling before acceptance', function (): void {
    $intervenant = makeQvctExchangeUser('intervenant', $this->structure);
    $req = QvctExchangeRequest::factory()->fromUser($intervenant)->toRh()->create();

    $service = app(ExchangeRequestService::class);

    expect(fn () => $service->schedule($req, now()->addDay()))
        ->toThrow(HttpException::class);
});

it('cannot accept a closed request', function (): void {
    $intervenant = makeQvctExchangeUser('intervenant', $this->structure);
    $rh = makeQvctExchangeUser('rh', $this->structure);
    $req = QvctExchangeRequest::factory()->fromUser($intervenant)->toRh()
        ->create(['status' => QvctExchangeStatus::Closed->value, 'closed_at' => now()]);

    $service = app(ExchangeRequestService::class);

    expect(fn () => $service->accept($req, $rh))->toThrow(HttpException::class);
});

// ── Privacy: who can see what ──────────────────────────────────────────────────

it('requester can always view their own request', function (): void {
    $intervenant = makeQvctExchangeUser('intervenant', $this->structure);
    $req = QvctExchangeRequest::factory()->fromUser($intervenant)->toRh()->create();

    expect($intervenant->can('view', $req))->toBeTrue();
});

it('rh can view incoming requests addressed to RH', function (): void {
    $intervenant = makeQvctExchangeUser('intervenant', $this->structure);
    $rh = makeQvctExchangeUser('rh', $this->structure);

    $req = QvctExchangeRequest::factory()->fromUser($intervenant)->toRh()->create();

    expect($rh->can('view', $req))->toBeTrue();
});

it('rh CANNOT view a request addressed to manager (not their queue)', function (): void {
    $intervenant = makeQvctExchangeUser('intervenant', $this->structure);
    $rh = makeQvctExchangeUser('rh', $this->structure);

    $req = QvctExchangeRequest::factory()->fromUser($intervenant)->toManager()->create();

    expect($rh->can('view', $req))->toBeFalse();
});

it('coordinateur can view requests addressed to manager', function (): void {
    $intervenant = makeQvctExchangeUser('intervenant', $this->structure);
    $coord = makeQvctExchangeUser('coordinateur', $this->structure);

    $req = QvctExchangeRequest::factory()->fromUser($intervenant)->toManager()->create();

    expect($coord->can('view', $req))->toBeTrue();
});

it('another intervenant in same tenant cannot view a request', function (): void {
    $a = makeQvctExchangeUser('intervenant', $this->structure);
    $b = makeQvctExchangeUser('intervenant', $this->structure);

    $req = QvctExchangeRequest::factory()->fromUser($b)->toRh()->create();

    expect($a->can('view', $req))->toBeFalse();
});

it('foreign-tenant rh cannot view a request from another tenant', function (): void {
    $foreignStructure = Structure::factory()->create();
    $foreignRh = makeQvctExchangeUser('rh', $foreignStructure);

    app()->instance('current_structure', $this->structure);
    $intervenant = makeQvctExchangeUser('intervenant', $this->structure);
    $req = QvctExchangeRequest::factory()->fromUser($intervenant)->toRh()->create();

    expect($foreignRh->can('view', $req))->toBeFalse();
});

// ── Cross-tenant scope ────────────────────────────────────────────────────────

it('only returns exchange requests from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();
    $userA = User::factory()->forStructure($a)->create();
    $userB = User::factory()->forStructure($b)->create();

    QvctExchangeRequest::factory()->fromUser($userA)->count(2)->create();
    QvctExchangeRequest::factory()->fromUser($userB)->count(3)->create();

    app()->instance('current_structure', $a);
    expect(QvctExchangeRequest::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(QvctExchangeRequest::count())->toBe(3);
});

it('encrypts the message column at rest', function (): void {
    $intervenant = makeQvctExchangeUser('intervenant', $this->structure);
    $req = QvctExchangeRequest::factory()->fromUser($intervenant)->toRh()->create([
        'message' => 'I would like to discuss confidential workload concerns.',
    ]);

    $rawRow = DB::table('qvct_exchange_requests')->where('id', $req->id)->first();
    expect($rawRow->message)->not->toContain('confidential');
    expect($req->fresh()->message)->toBe('I would like to discuss confidential workload concerns.');
});
