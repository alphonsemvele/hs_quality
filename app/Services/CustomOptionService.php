<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CustomOption;

class CustomOptionService
{
    /**
     * Return active custom options for a field as [{value, label}] pairs.
     *
     * Results are memoised per (field_key) within the current request so that
     * controllers and validation rules hitting the same field don't issue
     * duplicate queries.
     *
     * @return list<array{value: string, label: string}>
     */
    public function optionsFor(string $fieldKey): array
    {
        return once(
            fn () => CustomOption::query()
                ->active()
                ->forField($fieldKey)
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get(['value', 'label'])
                ->map(fn (CustomOption $o) => ['value' => $o->value, 'label' => $o->label])
                ->all()
        );
    }

    /**
     * Prepend the static enum-derived options then append the tenant's
     * custom options. The "Créer une nouvelle option" sentinel is added
     * by the frontend, not here.
     *
     * @param  list<array{value: string, label: string}>  $enumOptions
     * @return list<array{value: string, label: string}>
     */
    public function mergeWithEnum(array $enumOptions, string $fieldKey): array
    {
        return [...$enumOptions, ...$this->optionsFor($fieldKey)];
    }

    /**
     * Return the flat list of allowed values for a field (enum + custom).
     * Used by ValidOptionValue to run a single DB query per field per request.
     *
     * @param  list<string>  $enumValues
     * @return list<string>
     */
    public function allowedValues(string $fieldKey, array $enumValues): array
    {
        $custom = array_column($this->optionsFor($fieldKey), 'value');

        return [...$enumValues, ...$custom];
    }
}
