<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePilotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // L'autorisation sera gérée dans le controller via les policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['sometimes', 'exists:users,id'],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'date_of_birth' => ['sometimes', 'date', 'before:today'],
            'size_tshirt' => ['nullable', 'string', 'in:XS,S,M,L,XL,XXL'],
            'size_pants' => ['nullable', 'string', 'in:XS,S,M,L,XL,XXL'],
            'size_shoes' => ['nullable', 'integer', 'between:20,50'],
            'size_glove' => ['nullable', 'string', 'in:XS,S,M,L,XL'],
            'size_suit' => ['nullable', 'string', 'in:XS,S,M,L,XL,XXL'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'emergency_contact_name' => ['sometimes', 'string', 'max:255'],
            'emergency_contact_phone' => ['sometimes', 'string', 'max:255'],
            'is_minor' => ['sometimes', 'boolean'],
            'note' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'client_id.exists' => 'Le client sélectionné n\'existe pas.',
            'date_of_birth.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'email.email' => 'L\'email doit être valide.',
            'size_tshirt.in' => 'La taille de t-shirt doit être : XS, S, M, L, XL, XXL.',
            'size_pants.in' => 'La taille de pantalon doit être : XS, S, M, L, XL, XXL.',
            'size_glove.in' => 'La taille de gant doit être : XS, S, M, L, XL.',
            'size_suit.in' => 'La taille de combinaison doit être : XS, S, M, L, XL, XXL.',
            'size_shoes.between' => 'La pointure doit être entre 20 et 50.',
        ];
    }
}
