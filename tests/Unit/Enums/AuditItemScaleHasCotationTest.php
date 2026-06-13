<?php

declare(strict_types=1);

use App\Enums\AuditItemScale;

it('exposes HasCotation as a scale case with maxScore 4', function (): void {
    expect(AuditItemScale::HasCotation->value)->toBe('has_cotation')
        ->and(AuditItemScale::HasCotation->maxScore())->toBe(4.0)
        ->and(AuditItemScale::HasCotation->label())->toContain('A/B/C/D/NA');
});

it('maps each cotation code to its numeric score', function (string $code, ?float $expected): void {
    expect(AuditItemScale::scoreForCotation($code))->toBe($expected);
})->with([
    ['A', 4.0],
    ['B', 3.0],
    ['C', 2.0],
    ['D', 1.0],
    ['NA', null],
    ['unknown', null],
    ['', null],
]);

it('returns null when given null', function (): void {
    expect(AuditItemScale::scoreForCotation(null))->toBeNull();
});

it('returns the full list of cotation codes', function (): void {
    expect(AuditItemScale::cotationCodes())->toBe(['A', 'B', 'C', 'D', 'NA']);
});
