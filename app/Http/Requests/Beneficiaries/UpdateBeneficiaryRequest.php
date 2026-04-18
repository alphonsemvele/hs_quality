<?php

namespace App\Http\Requests\Beneficiaries;

use App\Enums\BeneficiaryStatus;
use App\Enums\Gender;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateBeneficiaryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('beneficiary'));
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
            'gender' => ['sometimes', 'nullable', new Enum(Gender::class)],

            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150'],

            'marital_status' => ['sometimes', 'nullable', 'string', 'max:50'],
            'gir' => ['sometimes', 'nullable', 'integer', 'between:1,6'],

            'primary_doctor' => ['sometimes', 'nullable', 'string', 'max:150'],
            'primary_doctor_phone' => ['sometimes', 'nullable', 'string', 'max:30'],

            'emergency_contact_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'emergency_contact_relationship' => ['sometimes', 'nullable', 'string', 'max:50'],

            'medical_notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'allergies' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'medical_history' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'current_treatments' => ['sometimes', 'nullable', 'string', 'max:2000'],

            'status' => ['sometimes', new Enum(BeneficiaryStatus::class)],
            'admitted_at' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'exited_at' => ['sometimes', 'nullable', 'date'],
            'exit_reason' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Le prénom est obligatoire.',
            'last_name.required' => 'Le nom est obligatoire.',

            'date_of_birth.date' => 'La date de naissance doit être une date valide.',
            'date_of_birth.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',

            'email.email' => 'L\'adresse email saisie n\'est pas valide.',
            'gir.between' => 'Le GIR doit être compris entre 1 et 6.',
        ];
    }
}
