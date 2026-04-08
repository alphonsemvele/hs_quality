<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'lastname' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($this->user)],
            'password' => ['nullable', 'string', 'min:8'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'fonction' => ['sometimes', 'string', 'max:100'],
            'specialite' => ['nullable', 'string', 'max:255'],
            'service_id' => ['nullable', 'exists:services,id'],
            'date_embauche' => ['nullable', 'date'],
            'statut' => ['sometimes', 'in:En service,En congé,En mission,Inactif'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ];
    }
}