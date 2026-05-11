<?php

declare(strict_types=1);

use App\Services\InterventionService;

/**
 * Unit coverage for InterventionService::mergeReportText() — the LWW +
 * git-style conflict-marker merge used when two writers race on
 * intervention.report_text. Spec: IMPLEMENTATION_PLAN.txt M4 W3
 * "last-writer-wins for most fields; both writers preserved as notes for
 * free-text reports with conflict markers".
 */
beforeEach(function (): void {
    $this->service = new InterventionService;
});

it('returns the existing value when incoming is null', function (): void {
    expect($this->service->mergeReportText('saved narrative', null))
        ->toBe('saved narrative');
});

it('returns the existing value when incoming is empty/whitespace', function (): void {
    expect($this->service->mergeReportText('saved', ''))->toBe('saved');
    expect($this->service->mergeReportText('saved', '   '))->toBe('saved');
    expect($this->service->mergeReportText('saved', "\n\t "))->toBe('saved');
});

it('returns the incoming value when existing is null/empty', function (): void {
    expect($this->service->mergeReportText(null, 'incoming'))->toBe('incoming');
    expect($this->service->mergeReportText('', 'incoming'))->toBe('incoming');
    expect($this->service->mergeReportText('   ', 'incoming'))->toBe('incoming');
});

it('returns one copy when both texts are identical', function (): void {
    expect($this->service->mergeReportText('same text', 'same text'))
        ->toBe('same text');
});

it('treats trim-equal texts as identical (no marker noise)', function (): void {
    expect($this->service->mergeReportText('  same text  ', "\nsame text\n"))
        ->toBe('same text');
});

it('wraps both values with git-style markers when they differ', function (): void {
    $merged = $this->service->mergeReportText(
        'Beneficiary calm, no pain.',
        'Beneficiary anxious, requested doctor.',
    );

    expect($merged)->toBe(
        "<<<<<<< saved\nBeneficiary calm, no pain.\n=======\nBeneficiary anxious, requested doctor.\n>>>>>>> incoming"
    );
});

it('returns null when both inputs are null/empty', function (): void {
    expect($this->service->mergeReportText(null, null))->toBeNull();
    expect($this->service->mergeReportText('', ''))->toBeNull();
});
