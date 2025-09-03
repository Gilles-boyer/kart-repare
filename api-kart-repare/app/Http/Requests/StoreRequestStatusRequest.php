<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequestStatusRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:50', 'unique:request_statuses,name'],
            'hex_color' => ['required', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'is_final' => ['boolean'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du statut est requis.',
            'name.max' => 'Le nom du statut ne peut pas dépasser 50 caractères.',
            'name.unique' => 'Ce nom de statut existe déjà.',
            'hex_color.required' => 'La couleur est requise.',
            'hex_color.regex' => 'La couleur doit être au format hexadécimal (#RRGGBB).',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Assurer que la couleur commence par #
        if ($this->hex_color && !str_starts_with($this->hex_color, '#')) {
            $this->merge([
                'hex_color' => '#' . $this->hex_color,
            ]);
        }

        // Convertir la couleur en majuscules pour la cohérence
        if ($this->hex_color) {
            $this->merge([
                'hex_color' => strtoupper($this->hex_color),
            ]);
        }
    }
}
