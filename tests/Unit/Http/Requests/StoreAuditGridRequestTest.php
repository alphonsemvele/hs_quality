<?php

declare(strict_types=1);

use App\Http\Requests\Audits\StoreAuditGridRequest;
use Illuminate\Support\Facades\Validator;

it('declares the expected validation rules for créating a référentiel', function (): void {
    $rules = (new StoreAuditGridRequest)->rules();

    expect($rules)->toHaveKeys(['title', 'description'])
        ->and($rules['title'])->toContain('required', 'string')
        ->and($rules['description'])->toContain('nullable', 'string');
});

it('rejects missing or oversized title', function (array $payload, string $field): void {
    $rules = (new StoreAuditGridRequest)->rules();

    $validator = Validator::make($payload, $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has($field))->toBeTrue();
})->with([
    'missing title' => [['description' => 'ok'], 'title'],
    'empty title' => [['title' => '', 'description' => 'ok'], 'title'],
    'title too long' => [['title' => str_repeat('a', 256)], 'title'],
    'description too long' => [['title' => 'ok', 'description' => str_repeat('a', 5001)], 'description'],
]);

it('accepts a valid payload', function (): void {
    $rules = (new StoreAuditGridRequest)->rules();

    $validator = Validator::make([
        'title' => 'Référentiel interne',
        'description' => 'Adapté du HAS.',
    ], $rules);

    expect($validator->fails())->toBeFalse();
});
