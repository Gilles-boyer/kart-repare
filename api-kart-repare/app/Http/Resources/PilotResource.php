<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PilotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'client' => new UserResource($this->whenLoaded('client')),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'age' => $this->age,
            'sizes' => [
                'tshirt' => $this->size_tshirt,
                'pants' => $this->size_pants,
                'shoes' => $this->size_shoes,
                'glove' => $this->size_glove,
                'suit' => $this->size_suit,
            ],
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'emergency_contact' => [
                'name' => $this->emergency_contact_name,
                'phone' => $this->emergency_contact_phone,
            ],
            'is_minor' => $this->is_minor,
            'note' => $this->note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
