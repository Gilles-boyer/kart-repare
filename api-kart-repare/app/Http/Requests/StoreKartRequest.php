<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKartRequest extends FormRequest
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
            'pilot_id' => ['required', 'exists:pilots,id'],
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'chassis_number' => ['required', 'string', 'max:255', 'unique:karts,chassis_number'],
            'year' => ['required', 'integer', 'min:1950', 'max:' . (date('Y') + 1)],
            'engine_type' => ['nullable', 'string', 'in:2T,4T,ELECTRIC'],
            'is_active' => ['boolean'],
            'note' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'pilot_id.required' => 'Le pilote est requis.',
            'pilot_id.exists' => 'Le pilote sélectionné n\'existe pas.',
            'brand.required' => 'La marque est requise.',
            'model.required' => 'Le modèle est requis.',
            'chassis_number.required' => 'Le numéro de châssis est requis.',
            'chassis_number.unique' => 'Ce numéro de châssis existe déjà.',
            'year.required' => 'L\'année est requise.',
            'year.min' => 'L\'année doit être supérieure à 1950.',
            'year.max' => 'L\'année ne peut pas être supérieure à ' . (date('Y') + 1) . '.',
            'engine_type.in' => 'Le type de moteur doit être : 2T, 4T ou ELECTRIC.',
        ];
    }
}
