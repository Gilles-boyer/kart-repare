<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKartRequest extends FormRequest
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
        $kart = $this->route('kart');

        return [
            'pilot_id' => ['sometimes', 'exists:pilots,id'],
            'brand' => ['sometimes', 'string', 'max:255'],
            'model' => ['sometimes', 'string', 'max:255'],
            'chassis_number' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('karts', 'chassis_number')->ignore($kart?->id)
            ],
            'year' => ['sometimes', 'integer', 'min:1950', 'max:' . (date('Y') + 1)],
            'engine_type' => ['nullable', 'string', 'in:2T,4T,ELECTRIC'],
            'is_active' => ['sometimes', 'boolean'],
            'note' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'pilot_id.exists' => 'Le pilote sélectionné n\'existe pas.',
            'chassis_number.unique' => 'Ce numéro de châssis existe déjà.',
            'year.min' => 'L\'année doit être supérieure à 1950.',
            'year.max' => 'L\'année ne peut pas être supérieure à ' . (date('Y') + 1) . '.',
            'engine_type.in' => 'Le type de moteur doit être : 2T, 4T ou ELECTRIC.',
        ];
    }
}
