<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\CustomOptionService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates that a submitted value is either one of the built-in enum values
 * OR an active custom option created by the tenant for this field.
 *
 * Usage:
 *   'gir' => ['nullable', new ValidOptionValue('gir', ['1','2','3','4','5','6'])],
 */
class ValidOptionValue implements ValidationRule
{
    public function __construct(
        private readonly string $fieldKey,
        private readonly array $enumValues = [],
    ) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $allowed = app(CustomOptionService::class)->allowedValues($this->fieldKey, $this->enumValues);

        if (! in_array((string) $value, $allowed, strict: true)) {
            $fail("La valeur sélectionnée pour :attribute n'est pas valide.");
        }
    }
}
