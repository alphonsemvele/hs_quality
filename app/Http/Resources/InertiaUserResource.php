<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Slim user shape shared globally with every Inertia page.
 *
 * Wave 1 / M1 — previously HandleInertiaRequests::share returned the full
 * Eloquent User model (every fillable column: structure_id, email, phone,
 * employee_number, type, hired_at, status, is_platform_admin, etc.) on
 * EVERY page response. That over-disclosed PII to anyone with browser
 * dev-tools and made it harder to reason about which fields are exposed
 * to the client.
 *
 * Add a field here only when a page actually needs it. If a single page
 * needs more, fetch it via a page-specific prop instead.
 */
class InertiaUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => trim($this->first_name.' '.$this->last_name),
            'type' => $this->type instanceof \BackedEnum ? $this->type->value : $this->type,
            'is_platform_admin' => (bool) $this->is_platform_admin,
            'requires_mfa' => $this->requiresMandatoryMfa(),
            'has_mfa_enrolled' => $this->two_factor_confirmed_at !== null,
        ];
    }
}
