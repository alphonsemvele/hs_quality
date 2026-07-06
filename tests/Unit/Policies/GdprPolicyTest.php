<?php

declare(strict_types=1);

use App\Models\AccountDeletionRequest;
use App\Models\DataExportRequest;
use App\Models\User;
use App\Policies\AccountDeletionRequestPolicy;
use App\Policies\DataExportRequestPolicy;

/**
 * Pure ownership-logic unit tests. Tenant isolation (BasePolicy::before) is
 * covered separately by the Gate-level feature tests; here we assert the
 * per-method ownership rules in isolation, without touching the database.
 */
function makeGdprPolicyUser(int $id): User
{
    $user = new User;
    $user->id = $id;

    return $user;
}

// ── DataExportRequestPolicy ──────────────────────────────────────────────────

it('lets the owner view and download their export, and no one else', function (): void {
    $policy = new DataExportRequestPolicy;
    $owner = makeGdprPolicyUser(7);
    $stranger = makeGdprPolicyUser(8);

    $export = new DataExportRequest;
    $export->user_id = 7;

    expect($policy->view($owner, $export))->toBeTrue()
        ->and($policy->download($owner, $export))->toBeTrue()
        ->and($policy->view($stranger, $export))->toBeFalse()
        ->and($policy->download($stranger, $export))->toBeFalse();
});

it('lets any authenticated user list and create an export of their own data', function (): void {
    $policy = new DataExportRequestPolicy;
    $user = makeGdprPolicyUser(7);

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->create($user))->toBeTrue();
});

// ── AccountDeletionRequestPolicy ─────────────────────────────────────────────

it('lets the owner view and cancel their deletion request, and no one else', function (): void {
    $policy = new AccountDeletionRequestPolicy;
    $owner = makeGdprPolicyUser(7);
    $stranger = makeGdprPolicyUser(8);

    $request = new AccountDeletionRequest;
    $request->user_id = 7;

    expect($policy->view($owner, $request))->toBeTrue()
        ->and($policy->cancel($owner, $request))->toBeTrue()
        ->and($policy->view($stranger, $request))->toBeFalse()
        ->and($policy->cancel($stranger, $request))->toBeFalse();
});

it('lets any authenticated user list and create their own deletion request', function (): void {
    $policy = new AccountDeletionRequestPolicy;
    $user = makeGdprPolicyUser(7);

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->create($user))->toBeTrue();
});
