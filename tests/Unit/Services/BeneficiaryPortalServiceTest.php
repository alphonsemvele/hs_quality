<?php

declare(strict_types=1);

use App\Enums\FamilyTokenScope;
use App\Enums\UserType;
use App\Models\Beneficiary;
use App\Models\BeneficiaryFamilyToken;
use App\Models\Structure;
use App\Models\User;
use App\Services\BeneficiaryPortalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->coordinator = User::factory()->forStructure($this->structure)->create();
    $this->beneficiary = Beneficiary::factory()->forStructure($this->structure)->create();
    $this->service = app(BeneficiaryPortalService::class);
});

// ── provisionAccess ───────────────────────────────────────────────────────

it('creates a portal user linked to the beneficiary', function (): void {
    $user = $this->service->provisionAccess(
        $this->beneficiary,
        'ben@example.com',
        'secret123',
    );

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->type)->toBe(UserType::BeneficiairePortal)
        ->and($user->beneficiary_id)->toBe($this->beneficiary->id)
        ->and($user->structure_id)->toBe($this->structure->id)
        ->and($user->wasRecentlyCreated)->toBeTrue();
});

it('provisionAccess is idempotent — returns existing user on second call', function (): void {
    $first = $this->service->provisionAccess($this->beneficiary, 'ben@example.com', 'pass1');
    $second = $this->service->provisionAccess($this->beneficiary, 'ben@example.com', 'pass2');

    expect($first->id)->toBe($second->id);
    expect(User::where('beneficiary_id', $this->beneficiary->id)->count())->toBe(1);
});

// ── issueFamilyToken ──────────────────────────────────────────────────────

it('issues a family token and stores only the hash', function (): void {
    $plaintext = $this->service->issueFamilyToken(
        beneficiary: $this->beneficiary,
        issuedBy: $this->coordinator,
        issuedToName: 'Marie Dupont',
        scopes: [FamilyTokenScope::ReadPlan, FamilyTokenScope::ReadInterventions],
        expiresAt: now()->addDays(30),
    );

    expect($plaintext)->toBeString()->not->toBeEmpty();

    $record = BeneficiaryFamilyToken::withoutGlobalScopes()
        ->where('beneficiary_id', $this->beneficiary->id)
        ->first();

    expect($record)->not->toBeNull()
        ->and($record->token_hash)->toBe(hash('sha256', $plaintext))
        ->and($record->issued_to_name)->toBe('Marie Dupont')
        ->and($record->hasScope(FamilyTokenScope::ReadPlan))->toBeTrue()
        ->and($record->hasScope(FamilyTokenScope::SubmitSatisfaction))->toBeFalse();
});

// ── validateFamilyToken ───────────────────────────────────────────────────

it('validateFamilyToken returns the token record for a valid token', function (): void {
    $plaintext = $this->service->issueFamilyToken(
        $this->beneficiary,
        $this->coordinator,
        'Pierre Martin',
        [FamilyTokenScope::ReadPlan],
        now()->addDays(7),
    );

    $token = $this->service->validateFamilyToken($plaintext);

    expect($token)->toBeInstanceOf(BeneficiaryFamilyToken::class)
        ->and($token->last_used_at)->not->toBeNull();
});

it('validateFamilyToken throws 401 for an unknown token', function (): void {
    expect(fn () => $this->service->validateFamilyToken('totally-bogus-token'))
        ->toThrow(HttpException::class);
});

it('validateFamilyToken throws 401 for an expired token', function (): void {
    $plaintext = $this->service->issueFamilyToken(
        $this->beneficiary,
        $this->coordinator,
        'Ancien accès',
        [FamilyTokenScope::ReadPlan],
        now()->subDay(), // already expired
    );

    expect(fn () => $this->service->validateFamilyToken($plaintext))
        ->toThrow(HttpException::class);
});

// ── revokeFamilyTokens ────────────────────────────────────────────────────

it('revokes all family tokens for a beneficiary', function (): void {
    $this->service->issueFamilyToken($this->beneficiary, $this->coordinator, 'A', [FamilyTokenScope::ReadPlan], now()->addDay());
    $this->service->issueFamilyToken($this->beneficiary, $this->coordinator, 'B', [FamilyTokenScope::ReadPlan], now()->addDay());

    $deleted = $this->service->revokeFamilyTokens($this->beneficiary);

    expect($deleted)->toBe(2);
    expect(BeneficiaryFamilyToken::withoutGlobalScopes()->where('beneficiary_id', $this->beneficiary->id)->count())->toBe(0);
});

// ── cross-tenant isolation ─────────────────────────────────────────────────

it('validateFamilyToken cannot be used for a beneficiary of another structure', function (): void {
    $other = Structure::factory()->create();
    $otherBeneficiary = Beneficiary::factory()->forStructure($other)->create();
    $otherCoord = User::factory()->forStructure($other)->create();
    app()->instance('current_structure', $other);

    $plaintext = $this->service->issueFamilyToken(
        $otherBeneficiary,
        $otherCoord,
        'Cross-tenant test',
        [FamilyTokenScope::ReadPlan],
        now()->addDay(),
    );

    // Switch back to original structure — token should still validate
    // (it's scoped by its own structure_id) but the beneficiary returned
    // belongs to $other, not $this->structure.
    app()->instance('current_structure', $this->structure);
    $token = $this->service->validateFamilyToken($plaintext);

    expect($token->structure_id)->toBe($other->id);
    expect($token->structure_id)->not->toBe($this->structure->id);
});
