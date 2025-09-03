<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRepairRequestProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'repair_request_id' => [
                'required',
                'integer',
                'exists:repair_requests,id,deleted_at,NULL',
            ],
            'product_id' => [
                'required',
                'integer',
                'exists:products,id,deleted_at,NULL',
                // Ensure unique combination with repair_request_id
                Rule::unique('repair_request_products')
                    ->where('repair_request_id', $this->repair_request_id),
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
            ],
            'priority' => [
                'required',
                'string',
                'in:high,medium,low',
            ],
            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'unit_price' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
                'decimal:0,2',
            ],
        ];
    }

    /**
     * Get custom validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'repair_request_id.required' => 'La demande de réparation est obligatoire.',
            'repair_request_id.exists' => 'La demande de réparation sélectionnée n\'existe pas.',
            'product_id.required' => 'Le produit est obligatoire.',
            'product_id.exists' => 'Le produit sélectionné n\'existe pas.',
            'product_id.unique' => 'Ce produit est déjà associé à cette demande de réparation.',
            'quantity.required' => 'La quantité est obligatoire.',
            'quantity.integer' => 'La quantité doit être un nombre entier.',
            'quantity.min' => 'La quantité doit être au moins de 1.',
            'quantity.max' => 'La quantité ne peut pas dépasser 1000.',
            'priority.required' => 'La priorité est obligatoire.',
            'priority.in' => 'La priorité doit être : haute, moyenne ou basse.',
            'note.max' => 'La note ne peut pas dépasser 1000 caractères.',
            'unit_price.required' => 'Le prix unitaire est obligatoire.',
            'unit_price.numeric' => 'Le prix unitaire doit être un nombre.',
            'unit_price.min' => 'Le prix unitaire ne peut pas être négatif.',
            'unit_price.max' => 'Le prix unitaire ne peut pas dépasser 99,999,999.99.',
            'unit_price.decimal' => 'Le prix unitaire ne peut avoir que 2 décimales maximum.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'repair_request_id' => 'demande de réparation',
            'product_id' => 'produit',
            'quantity' => 'quantité',
            'priority' => 'priorité',
            'note' => 'note',
            'unit_price' => 'prix unitaire',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Calculate total_price automatically
        if ($this->has('quantity') && $this->has('unit_price')) {
            $this->merge([
                'total_price' => (float) $this->quantity * (float) $this->unit_price,
            ]);
        }

        // Convert priority to lowercase for consistency
        if ($this->has('priority')) {
            $this->merge([
                'priority' => strtolower($this->priority),
            ]);
        }
    }

    /**
     * Get validated data with computed total_price.
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        // Add computed total_price
        if (isset($data['quantity'], $data['unit_price'])) {
            $data['total_price'] = (float) $data['quantity'] * (float) $data['unit_price'];
        }

        return $data;
    }
}
