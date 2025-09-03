<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KartResource extends JsonResource
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
            'pilot_id' => $this->pilot_id,
            'pilot' => new PilotResource($this->whenLoaded('pilot')),
            'client' => new UserResource($this->whenLoaded('client')),
            'brand' => $this->brand,
            'model' => $this->model,
            'chassis_number' => $this->chassis_number,
            'full_identification' => $this->full_identification,
            'year' => $this->year,
            'engine_type' => $this->engine_type,
            'is_active' => $this->is_active,
            'status' => $this->is_active ? 'Active' : 'Inactive',
            'note' => $this->note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
