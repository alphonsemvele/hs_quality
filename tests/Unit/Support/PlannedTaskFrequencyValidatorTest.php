<?php

use App\Support\PlannedTaskFrequencyValidator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;

/**
 * Schema-driven validation of frequency_details per TaskFrequency.
 * Every accepted shape AND every rejected shape is covered.
 */
function buildPlannedTaskValidator(array $data): Validator
{
    $validator = ValidatorFacade::make($data, []);
    PlannedTaskFrequencyValidator::validate($validator, $data);

    return $validator;
}

it('accepts a null frequency_details', function () {
    $v = buildPlannedTaskValidator(['frequency' => 'daily', 'frequency_details' => null]);
    expect($v->errors()->isEmpty())->toBeTrue();
});

// ─── Daily ──────────────────────────────────────────────────────────────────

it('accepts daily with no extras', function () {
    $v = buildPlannedTaskValidator(['frequency' => 'daily', 'frequency_details' => []]);
    expect($v->errors()->isEmpty())->toBeTrue();
});

it('accepts daily with valid times_per_day', function () {
    $v = buildPlannedTaskValidator(['frequency' => 'daily', 'frequency_details' => ['times_per_day' => 3]]);
    expect($v->errors()->isEmpty())->toBeTrue();
});

it('rejects daily times_per_day out of range', function () {
    $v = buildPlannedTaskValidator(['frequency' => 'daily', 'frequency_details' => ['times_per_day' => 7]]);
    expect($v->errors()->has('frequency_details.times_per_day'))->toBeTrue();
});

it('rejects daily with unknown key', function () {
    $v = buildPlannedTaskValidator(['frequency' => 'daily', 'frequency_details' => ['days' => ['mon']]]);
    expect($v->errors()->has('frequency_details.days'))->toBeTrue();
});

// ─── Weekly ─────────────────────────────────────────────────────────────────

it('accepts weekly with valid days', function () {
    $v = buildPlannedTaskValidator(['frequency' => 'weekly', 'frequency_details' => ['days' => ['mon', 'wed', 'fri']]]);
    expect($v->errors()->isEmpty())->toBeTrue();
});

it('rejects weekly without days', function () {
    $v = buildPlannedTaskValidator(['frequency' => 'weekly', 'frequency_details' => []]);
    expect($v->errors()->has('frequency_details.days'))->toBeTrue();
});

it('rejects weekly with invalid day', function () {
    $v = buildPlannedTaskValidator(['frequency' => 'weekly', 'frequency_details' => ['days' => ['mon', 'funday']]]);
    expect($v->errors()->has('frequency_details.days'))->toBeTrue();
});

it('rejects weekly times_per_week out of range', function () {
    $v = buildPlannedTaskValidator([
        'frequency' => 'weekly',
        'frequency_details' => ['days' => ['mon'], 'times_per_week' => 20],
    ]);
    expect($v->errors()->has('frequency_details.times_per_week'))->toBeTrue();
});

// ─── Monthly ────────────────────────────────────────────────────────────────

it('accepts monthly with day_of_month', function () {
    $v = buildPlannedTaskValidator(['frequency' => 'monthly', 'frequency_details' => ['day_of_month' => 15]]);
    expect($v->errors()->isEmpty())->toBeTrue();
});

it('accepts monthly with week + day', function () {
    $v = buildPlannedTaskValidator([
        'frequency' => 'monthly',
        'frequency_details' => ['week' => 'first', 'day' => 'mon'],
    ]);
    expect($v->errors()->isEmpty())->toBeTrue();
});

it('rejects monthly without any anchor', function () {
    $v = buildPlannedTaskValidator(['frequency' => 'monthly', 'frequency_details' => []]);
    expect($v->errors()->has('frequency_details'))->toBeTrue();
});

it('rejects monthly with day_of_month AND week together', function () {
    $v = buildPlannedTaskValidator([
        'frequency' => 'monthly',
        'frequency_details' => ['day_of_month' => 5, 'week' => 'first'],
    ]);
    expect($v->errors()->has('frequency_details'))->toBeTrue();
});

it('rejects monthly with day_of_month > 31', function () {
    $v = buildPlannedTaskValidator(['frequency' => 'monthly', 'frequency_details' => ['day_of_month' => 32]]);
    expect($v->errors()->has('frequency_details.day_of_month'))->toBeTrue();
});

it('rejects monthly with invalid week position', function () {
    $v = buildPlannedTaskValidator([
        'frequency' => 'monthly',
        'frequency_details' => ['week' => 'random', 'day' => 'mon'],
    ]);
    expect($v->errors()->has('frequency_details.week'))->toBeTrue();
});

// ─── OnDemand ───────────────────────────────────────────────────────────────

it('accepts on_demand with empty details', function () {
    $v = buildPlannedTaskValidator(['frequency' => 'on_demand', 'frequency_details' => []]);
    expect($v->errors()->isEmpty())->toBeTrue();
});

it('rejects on_demand with any extras', function () {
    $v = buildPlannedTaskValidator(['frequency' => 'on_demand', 'frequency_details' => ['times_per_day' => 1]]);
    expect($v->errors()->has('frequency_details'))->toBeTrue();
});

// ─── Custom ─────────────────────────────────────────────────────────────────

it('accepts custom with description', function () {
    $v = buildPlannedTaskValidator([
        'frequency' => 'custom',
        'frequency_details' => ['description' => '2 fois la première semaine du mois'],
    ]);
    expect($v->errors()->isEmpty())->toBeTrue();
});

it('rejects custom without description', function () {
    $v = buildPlannedTaskValidator(['frequency' => 'custom', 'frequency_details' => []]);
    expect($v->errors()->has('frequency_details.description'))->toBeTrue();
});

it('rejects custom with description > 500 chars', function () {
    $v = buildPlannedTaskValidator([
        'frequency' => 'custom',
        'frequency_details' => ['description' => str_repeat('a', 501)],
    ]);
    expect($v->errors()->has('frequency_details.description'))->toBeTrue();
});
