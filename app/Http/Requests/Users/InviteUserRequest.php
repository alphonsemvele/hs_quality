<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use App\Enums\UserType;
use App\Http\Requests\BaseFormRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * Validate POST /users — invite a new team member to the current tenant.
 *
 * Authorization is split: viewAny gets the inviter's foot in the door,
 * then UserPolicy::invite($role) decides whether the inviter can mint
 * THIS specific role. Lets us forbid e.g. a coordinateur inviting a
 * dirigeant without losing the generic "manage users" permission check.
 */
class InviteUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        // Two-layer gate:
        //   - viewAny here so the form returns 403 BEFORE field validation
        //     leaks the form shape to anyone unauthorized.
        //   - invite($role) is checked AFTER validation in the controller
        //     so legitimate users get clean field errors when both the role
        //     and the field shape are wrong.
        return $this->user()?->can('viewAny', User::class) === true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'type' => ['required', Rule::enum(UserType::class)],
            'phone' => ['nullable', 'string', 'max:50'],
            'employee_number' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Un utilisateur avec cet email existe déjà.',
            'type.required' => 'Le rôle est obligatoire.',
            'type.enum' => 'Rôle inconnu.',
        ];
    }
}
