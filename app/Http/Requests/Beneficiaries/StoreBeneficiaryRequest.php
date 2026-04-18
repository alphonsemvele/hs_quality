<?php

namespace App\Http\Requests\Beneficiaries;

use App\Enums\Gender;
use App\Http\Requests\BaseFormRequest;
use App\Models\Beneficiary;
use Illuminate\Validation\Rules\Enum;

class StoreBeneficiaryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Beneficiary::class);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', new Enum(Gender::class)],

            'address' => ['nullable', 'string', 'max:500'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],

            'marital_status' => ['nullable', 'string', 'max:50'],
            'gir' => ['nullable', 'integer', 'between:1,6'],

            'primary_doctor' => ['nullable', 'string', 'max:150'],
            'primary_doctor_phone' => ['nullable', 'string', 'max:30'],

            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:50'],

            'medical_notes' => ['nullable', 'string', 'max:5000'],
            'allergies' => ['nullable', 'string', 'max:1000'],
            'medical_history' => ['nullable', 'string', 'max:5000'],
            'current_treatments' => ['nullable', 'string', 'max:2000'],

            'admitted_at' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * User-facing validation messages stay French — that's the product's
     * target language. Translation key pattern will come with the lang/fr
     * convention in a later phase.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Le prénom est obligatoire.',
            'first_name.max' => 'Le prénom ne peut pas dépasser :max caractères.',
            'last_name.required' => 'Le nom est obligatoire.',
            'last_name.max' => 'Le nom ne peut pas dépasser :max caractères.',

            'date_of_birth.date' => 'La date de naissance doit être une date valide.',
            'date_of_birth.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',

            'email.email' => 'L\'adresse email saisie n\'est pas valide.',

            'gir.integer' => 'Le GIR doit être un nombre entier.',
            'gir.between' => 'Le GIR doit être compris entre 1 et 6.',

            'medical_notes.max' => 'Les notes médicales ne peuvent pas dépasser :max caractères.',

            'admitted_at.before_or_equal' => 'La date d\'entrée ne peut pas être dans le futur.',
        ];
    }
}
