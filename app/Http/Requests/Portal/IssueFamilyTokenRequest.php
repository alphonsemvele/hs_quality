<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use App\Enums\FamilyTokenScope;
use App\Http\Requests\BaseFormRequest;
use App\Models\Beneficiary;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class IssueFamilyTokenRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        /** @var Beneficiary $beneficiary */
        $beneficiary = $this->route('beneficiary');

        return $this->user()?->can('update', $beneficiary) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'issued_to_name' => ['required', 'string', 'max:120'],
            'expires_at' => ['required', 'date', 'after:now', 'before:'.now()->addYear()->toDateString()],
            'scope' => ['required', 'array', 'min:1'],
            'scope.*' => ['required', 'string', Rule::enum(FamilyTokenScope::class)],
        ];
    }

    /** @return list<FamilyTokenScope> */
    public function validatedScopes(): array
    {
        return array_map(
            fn (string $s) => FamilyTokenScope::from($s),
            $this->validated('scope'),
        );
    }

    public function validatedExpiresAt(): Carbon
    {
        return Carbon::parse($this->validated('expires_at'));
    }
}
