<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\TaskFrequency;
use Illuminate\Contracts\Validation\Validator;

/**
 * Schema-aware validator for `frequency_details` on a planned task.
 *
 * The base FormRequest accepts any array; this helper enforces what shape
 * the array must take per `frequency` value. Called from `withValidator`
 * on both Store and Update requests so the two surfaces stay in sync.
 *
 * Accepted shapes:
 *   - daily     → empty, or { times_per_day: int 1..6 }
 *   - weekly    → { days: [string, …] } where each day is one of mon..sun,
 *                 optionally { times_per_week: int 1..14 }
 *   - monthly   → { day_of_month: int 1..31 }
 *                 OR { week: 'first'|'second'|'third'|'fourth'|'last',
 *                      day: 'mon'..'sun' }
 *   - on_demand → empty (no extras allowed)
 *   - custom    → { description: string ≤ 500 chars }
 */
class PlannedTaskFrequencyValidator
{
    private const VALID_WEEK_DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    private const VALID_WEEK_POSITIONS = ['first', 'second', 'third', 'fourth', 'last'];

    /**
     * @param  array<string, mixed>  $data
     */
    public static function validate(Validator $validator, array $data): void
    {
        $frequency = $data['frequency'] ?? null;
        $details = $data['frequency_details'] ?? null;

        if ($details === null) {
            return; // null details are always allowed
        }

        if (! is_array($details)) {
            return; // base rule already flags non-array values
        }

        $enum = is_string($frequency) ? TaskFrequency::tryFrom($frequency) : null;

        match ($enum) {
            TaskFrequency::Daily => self::validateDaily($validator, $details),
            TaskFrequency::Weekly => self::validateWeekly($validator, $details),
            TaskFrequency::Monthly => self::validateMonthly($validator, $details),
            TaskFrequency::OnDemand => self::validateOnDemand($validator, $details),
            TaskFrequency::Custom => self::validateCustom($validator, $details),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private static function validateDaily(Validator $validator, array $details): void
    {
        $allowed = ['times_per_day'];
        self::rejectUnknown($validator, $details, $allowed);

        if (array_key_exists('times_per_day', $details)) {
            $value = $details['times_per_day'];
            if (! is_int($value) || $value < 1 || $value > 6) {
                $validator->errors()->add(
                    'frequency_details.times_per_day',
                    'times_per_day doit être un entier entre 1 et 6.',
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private static function validateWeekly(Validator $validator, array $details): void
    {
        $allowed = ['days', 'times_per_week'];
        self::rejectUnknown($validator, $details, $allowed);

        if (! isset($details['days']) || ! is_array($details['days']) || $details['days'] === []) {
            $validator->errors()->add(
                'frequency_details.days',
                'days doit être un tableau non vide (lun, mar, …).',
            );
        } else {
            foreach ($details['days'] as $day) {
                if (! is_string($day) || ! in_array($day, self::VALID_WEEK_DAYS, true)) {
                    $validator->errors()->add(
                        'frequency_details.days',
                        'Jour invalide : "'.(is_string($day) ? $day : 'non-string').'". Valeurs autorisées : '.implode(', ', self::VALID_WEEK_DAYS).'.',
                    );
                    break;
                }
            }
        }

        if (array_key_exists('times_per_week', $details)) {
            $value = $details['times_per_week'];
            if (! is_int($value) || $value < 1 || $value > 14) {
                $validator->errors()->add(
                    'frequency_details.times_per_week',
                    'times_per_week doit être un entier entre 1 et 14.',
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private static function validateMonthly(Validator $validator, array $details): void
    {
        $hasDayOfMonth = array_key_exists('day_of_month', $details);
        $hasWeekPos = array_key_exists('week', $details) || array_key_exists('day', $details);

        if (! $hasDayOfMonth && ! $hasWeekPos) {
            $validator->errors()->add(
                'frequency_details',
                'Pour une fréquence mensuelle, fournissez soit day_of_month, soit week + day.',
            );

            return;
        }

        if ($hasDayOfMonth && $hasWeekPos) {
            $validator->errors()->add(
                'frequency_details',
                'day_of_month et week/day sont mutuellement exclusifs.',
            );

            return;
        }

        if ($hasDayOfMonth) {
            self::rejectUnknown($validator, $details, ['day_of_month']);

            $value = $details['day_of_month'];
            if (! is_int($value) || $value < 1 || $value > 31) {
                $validator->errors()->add(
                    'frequency_details.day_of_month',
                    'day_of_month doit être un entier entre 1 et 31.',
                );
            }

            return;
        }

        self::rejectUnknown($validator, $details, ['week', 'day']);

        $week = $details['week'] ?? null;
        if (! is_string($week) || ! in_array($week, self::VALID_WEEK_POSITIONS, true)) {
            $validator->errors()->add(
                'frequency_details.week',
                'week doit être une valeur parmi : '.implode(', ', self::VALID_WEEK_POSITIONS).'.',
            );
        }

        $day = $details['day'] ?? null;
        if (! is_string($day) || ! in_array($day, self::VALID_WEEK_DAYS, true)) {
            $validator->errors()->add(
                'frequency_details.day',
                'day doit être une valeur parmi : '.implode(', ', self::VALID_WEEK_DAYS).'.',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private static function validateOnDemand(Validator $validator, array $details): void
    {
        if ($details !== []) {
            $validator->errors()->add(
                'frequency_details',
                'frequency_details doit être vide pour une fréquence "à la demande".',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private static function validateCustom(Validator $validator, array $details): void
    {
        self::rejectUnknown($validator, $details, ['description']);

        if (! isset($details['description']) || ! is_string($details['description']) || trim($details['description']) === '') {
            $validator->errors()->add(
                'frequency_details.description',
                'description est obligatoire pour une fréquence personnalisée.',
            );

            return;
        }

        if (mb_strlen($details['description']) > 500) {
            $validator->errors()->add(
                'frequency_details.description',
                'description ne peut pas dépasser 500 caractères.',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $details
     * @param  list<string>  $allowed
     */
    private static function rejectUnknown(Validator $validator, array $details, array $allowed): void
    {
        foreach (array_keys($details) as $key) {
            if (! in_array($key, $allowed, true)) {
                $validator->errors()->add(
                    'frequency_details.'.$key,
                    'Clé inconnue : "'.$key.'". Clés autorisées : '.implode(', ', $allowed).'.',
                );
            }
        }
    }
}
