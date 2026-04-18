<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base Form Request. Every domain Form Request extends this class so
 * common helpers (current tenant ID, shared French validation messages)
 * are available without repetition.
 *
 * Subclasses override authorize() and rules(). Never validate inline in
 * a controller on this project — use Form Requests everywhere.
 *
 * See: references/conventions/security-first.md
 *      assets/templates/form-request.php
 */
abstract class BaseFormRequest extends FormRequest
{
    /**
     * Return the currently bound tenant's UUID. Useful for tenant-aware
     * exists:table,id,structure_id,<value> validation rules.
     */
    protected function currentStructureId(): ?string
    {
        $tenant = currentStructure();

        return $tenant?->getKey();
    }

    /**
     * Default to denying by returning false; subclasses MUST override with
     * an explicit authorization check (role permission, Policy invocation,
     * ownership rule, etc.).
     *
     * A Form Request without an overridden authorize() implicitly denies —
     * surface the denial as a 403 rather than silently authorizing.
     */
    public function authorize(): bool
    {
        return false;
    }
}
