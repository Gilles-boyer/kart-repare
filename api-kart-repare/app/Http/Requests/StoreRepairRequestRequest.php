<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRepairRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kart_id' => [
                'required',
                'integer',
                'exists:karts,id,deleted_at,NULL'
            ],
            'title' => [
                'required',
                'string',
                'max:255',
                'min:3'
            ],
            'description' => [
                'nullable',
                'string',
                'max:5000'
            ],
            'status_id' => [
                'required',
                'integer',
                'exists:request_statuses,id,deleted_at,NULL'
            ],
            'priority' => [
                'required',
                Rule::in(['low', 'medium', 'high'])
            ],
            'assigned_to' => [
                'nullable',
                'integer',
                'exists:users,id,deleted_at,NULL'
            ],
            'estimated_cost' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99'
            ],
            'actual_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99'
            ],
            'estimated_completion' => [
                'nullable',
                'date',
                'after_or_equal:today'
            ],
            'started_at' => [
                'nullable',
                'date',
                'before_or_equal:now'
            ],
            'completed_at' => [
                'nullable',
                'date',
                'before_or_equal:now',
                'after_or_equal:started_at'
            ],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kart_id.required' => 'Le kart est obligatoire.',
            'kart_id.exists' => 'Le kart sélectionné n\'existe pas.',
            'title.required' => 'Le titre est obligatoire.',
            'title.min' => 'Le titre doit contenir au moins :min caractères.',
            'title.max' => 'Le titre ne peut pas dépasser :max caractères.',
            'description.max' => 'La description ne peut pas dépasser :max caractères.',
            'status_id.required' => 'Le statut est obligatoire.',
            'status_id.exists' => 'Le statut sélectionné n\'existe pas.',
            'priority.required' => 'La priorité est obligatoire.',
            'priority.in' => 'La priorité doit être faible, moyenne ou haute.',
            'assigned_to.exists' => 'L\'utilisateur assigné n\'existe pas.',
            'estimated_cost.required' => 'Le coût estimé est obligatoire.',
            'estimated_cost.numeric' => 'Le coût estimé doit être un nombre.',
            'estimated_cost.min' => 'Le coût estimé doit être positif.',
            'estimated_cost.max' => 'Le coût estimé ne peut pas dépasser :max.',
            'actual_cost.numeric' => 'Le coût réel doit être un nombre.',
            'actual_cost.min' => 'Le coût réel doit être positif.',
            'actual_cost.max' => 'Le coût réel ne peut pas dépasser :max.',
            'estimated_completion.date' => 'La date d\'achèvement estimée doit être une date valide.',
            'estimated_completion.after_or_equal' => 'La date d\'achèvement estimée ne peut pas être dans le passé.',
            'started_at.date' => 'La date de début doit être une date valide.',
            'started_at.before_or_equal' => 'La date de début ne peut pas être dans le futur.',
            'completed_at.date' => 'La date de fin doit être une date valide.',
            'completed_at.before_or_equal' => 'La date de fin ne peut pas être dans le futur.',
            'completed_at.after_or_equal' => 'La date de fin doit être postérieure à la date de début.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Assigner l'utilisateur créateur automatiquement
        $this->merge([
            'created_by' => $this->user()->id,
        ]);
    }

    /**
     * Get the validated data from the request.
     *
     * @return array
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        // S'assurer que created_by est inclus dans les données validées
        $data['created_by'] = $this->user()->id;

        return $data;
    }
}
