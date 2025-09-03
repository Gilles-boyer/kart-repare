<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePilotRequest extends FormRequest
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
            'client_id' => ['required', 'exists:users,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'size_tshirt' => ['nullable', 'string', 'in:XS,S,M,L,XL,XXL'],
            'size_pants' => ['nullable', 'string', 'in:XS,S,M,L,XL,XXL'],
            'size_shoes' => ['nullable', 'integer', 'between:20,50'],
            'size_glove' => ['nullable', 'string', 'in:XS,S,M,L,XL'],
            'size_suit' => ['nullable', 'string', 'in:XS,S,M,L,XL,XXL'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'string', 'max:255'],
            'is_minor' => ['boolean'],
            'note' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'Le client est requis.',
            'client_id.exists' => 'Le client sélectionné n\'existe pas.',
            'first_name.required' => 'Le prénom est requis.',
            'last_name.required' => 'Le nom est requis.',
            'date_of_birth.required' => 'La date de naissance est requise.',
            'date_of_birth.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'emergency_contact_name.required' => 'Le nom du contact d\'urgence est requis.',
            'emergency_contact_phone.required' => 'Le téléphone du contact d\'urgence est requis.',
            'email.email' => 'L\'email doit être valide.',
            'size_tshirt.in' => 'La taille de t-shirt doit être : XS, S, M, L, XL, XXL.',
            'size_pants.in' => 'La taille de pantalon doit être : XS, S, M, L, XL, XXL.',
            'size_glove.in' => 'La taille de gant doit être : XS, S, M, L, XL.',
            'size_suit.in' => 'La taille de combinaison doit être : XS, S, M, L, XL, XXL.',
            'size_shoes.between' => 'La pointure doit être entre 20 et 50.',
        ];
    }
}
