<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRepairRequestRequest extends FormRequest
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
                'sometimes',
                'required',
                'integer',
                'exists:karts,id,deleted_at,NULL'
            ],
            'title' => [
                'sometimes',
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
                'sometimes',
                'required',
                'integer',
                'exists:request_statuses,id,deleted_at,NULL'
            ],
            'priority' => [
                'sometimes',
                'required',
                Rule::in(['low', 'medium', 'high'])
            ],
            'assigned_to' => [
                'nullable',
                'integer',
                'exists:users,id,deleted_at,NULL'
            ],
            'estimated_cost' => [
                'sometimes',
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
                'date'
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
            'started_at.date' => 'La date de début doit être une date valide.',
            'started_at.before_or_equal' => 'La date de début ne peut pas être dans le futur.',
            'completed_at.date' => 'La date de fin doit être une date valide.',
            'completed_at.before_or_equal' => 'La date de fin ne peut pas être dans le futur.',
            'completed_at.after_or_equal' => 'La date de fin doit être postérieure à la date de début.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validation business logic
            $data = $this->validated();

            // Si on marque comme terminé, il faut une date de début
            if (isset($data['completed_at']) && $data['completed_at'] && !isset($data['started_at'])) {
                $repairRequest = $this->route('repair_request');
                if ($repairRequest && !$repairRequest->started_at) {
                    $validator->errors()->add('started_at', 'La date de début est requise pour marquer la réparation comme terminée.');
                }
            }

            // Si on assigne un coût réel, la réparation devrait être terminée ou en cours
            if (isset($data['actual_cost']) && $data['actual_cost'] > 0) {
                if (!isset($data['started_at']) && (!$this->route('repair_request') || !$this->route('repair_request')->started_at)) {
                    $validator->errors()->add('actual_cost', 'Un coût réel ne peut être ajouté que pour une réparation commencée.');
                }
            }
        });
    }
}
