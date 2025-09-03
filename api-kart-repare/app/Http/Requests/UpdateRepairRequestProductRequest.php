<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRepairRequestProductRequest extends FormRequest
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
        $repairRequestProduct = $this->route('repair_request_product');

        return [
            'quantity' => [
                'sometimes',
                'integer',
                'min:1',
                'max:1000',
            ],
            'priority' => [
                'sometimes',
                'string',
                'in:high,medium,low',
            ],
            'note' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
            'unit_price' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:99999999.99',
                'decimal:0,2',
            ],
            // These fields should only be updated through specific actions
            'invoiced_by' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:users,id,deleted_at,NULL',
            ],
            'invoiced_at' => [
                'sometimes',
                'nullable',
                'date',
            ],
            'completed_by' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:users,id,deleted_at,NULL',
            ],
            'completed_at' => [
                'sometimes',
                'nullable',
                'date',
            ],
            'approved_at' => [
                'sometimes',
                'nullable',
                'date',
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
            'quantity.integer' => 'La quantité doit être un nombre entier.',
            'quantity.min' => 'La quantité doit être au moins de 1.',
            'quantity.max' => 'La quantité ne peut pas dépasser 1000.',
            'priority.in' => 'La priorité doit être : haute, moyenne ou basse.',
            'note.max' => 'La note ne peut pas dépasser 1000 caractères.',
            'unit_price.numeric' => 'Le prix unitaire doit être un nombre.',
            'unit_price.min' => 'Le prix unitaire ne peut pas être négatif.',
            'unit_price.max' => 'Le prix unitaire ne peut pas dépasser 99,999,999.99.',
            'unit_price.decimal' => 'Le prix unitaire ne peut avoir que 2 décimales maximum.',
            'invoiced_by.exists' => 'L\'utilisateur sélectionné pour la facturation n\'existe pas.',
            'completed_by.exists' => 'L\'utilisateur sélectionné pour la completion n\'existe pas.',
            'invoiced_at.date' => 'La date de facturation doit être une date valide.',
            'completed_at.date' => 'La date de completion doit être une date valide.',
            'approved_at.date' => 'La date d\'approbation doit être une date valide.',
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
            'quantity' => 'quantité',
            'priority' => 'priorité',
            'note' => 'note',
            'unit_price' => 'prix unitaire',
            'invoiced_by' => 'facturé par',
            'completed_by' => 'terminé par',
            'invoiced_at' => 'date de facturation',
            'completed_at' => 'date de completion',
            'approved_at' => 'date d\'approbation',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert priority to lowercase for consistency
        if ($this->has('priority')) {
            $this->merge([
                'priority' => strtolower($this->priority),
            ]);
        }
    }

    /**
     * Get validated data with computed total_price if needed.
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        // If quantity or unit_price is being updated, we need to recalculate total_price
        $repairRequestProduct = $this->route('repair_request_product');

        if (isset($data['quantity']) || isset($data['unit_price'])) {
            $quantity = $data['quantity'] ?? $repairRequestProduct->quantity;
            $unitPrice = $data['unit_price'] ?? $repairRequestProduct->unit_price;

            $data['total_price'] = (float) $quantity * (float) $unitPrice;
        }

        return $data;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $repairRequestProduct = $this->route('repair_request_product');

            // Validate workflow state transitions
            if ($this->has('invoiced_at') && !$repairRequestProduct->canBeInvoiced()) {
                $validator->errors()->add('invoiced_at', 'Ce produit ne peut pas être facturé dans son état actuel.');
            }

            if ($this->has('completed_at') && !$repairRequestProduct->canBeCompleted()) {
                $validator->errors()->add('completed_at', 'Ce produit ne peut pas être marqué comme terminé dans son état actuel.');
            }

            if ($this->has('approved_at') && !$repairRequestProduct->canBeApproved()) {
                $validator->errors()->add('approved_at', 'Ce produit ne peut pas être approuvé dans son état actuel.');
            }

            // Validate chronological order
            if ($this->has('completed_at') && $this->has('invoiced_at')) {
                $completedAt = $this->date('completed_at');
                $invoicedAt = $this->date('invoiced_at');

                if ($completedAt && $invoicedAt && $completedAt->isBefore($invoicedAt)) {
                    $validator->errors()->add('completed_at', 'La date de completion ne peut pas être antérieure à la date de facturation.');
                }
            }

            if ($this->has('approved_at') && $this->has('completed_at')) {
                $approvedAt = $this->date('approved_at');
                $completedAt = $this->date('completed_at');

                if ($approvedAt && $completedAt && $approvedAt->isBefore($completedAt)) {
                    $validator->errors()->add('approved_at', 'La date d\'approbation ne peut pas être antérieure à la date de completion.');
                }
            }
        });
    }
}
