<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' || $this->user()?->role === 'bureau_staff';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ref' => 'required|string|max:100|unique:products,ref',
            'price' => 'required|numeric|min:0|max:999999.99',
            'image' => 'nullable|string',
            'in_stock' => 'required|integer|min:0',
            'unity' => 'required|in:piece,hours,liters,kg',
            'min_stock' => 'required|integer|min:0',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du produit est obligatoire.',
            'name.max' => 'Le nom du produit ne peut pas dépasser 255 caractères.',
            'ref.required' => 'La référence du produit est obligatoire.',
            'ref.max' => 'La référence ne peut pas dépasser 100 caractères.',
            'ref.unique' => 'Cette référence existe déjà.',
            'price.required' => 'Le prix est obligatoire.',
            'price.numeric' => 'Le prix doit être un nombre.',
            'price.min' => 'Le prix ne peut pas être négatif.',
            'price.max' => 'Le prix ne peut pas dépasser 999,999.99.',
            'in_stock.required' => 'La quantité en stock est obligatoire.',
            'in_stock.integer' => 'La quantité en stock doit être un nombre entier.',
            'in_stock.min' => 'La quantité en stock ne peut pas être négative.',
            'unity.required' => 'L\'unité est obligatoire.',
            'unity.in' => 'L\'unité doit être : pièce, heures, litres ou kg.',
            'min_stock.required' => 'Le stock minimum est obligatoire.',
            'min_stock.integer' => 'Le stock minimum doit être un nombre entier.',
            'min_stock.min' => 'Le stock minimum ne peut pas être négatif.',
        ];
    }
}
