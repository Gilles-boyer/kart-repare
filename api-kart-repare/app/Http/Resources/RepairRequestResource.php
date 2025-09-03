<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepairRequestResource extends JsonResource
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
            'kart_id' => $this->kart_id,
            'title' => $this->title,
            'description' => $this->description,
            'status_id' => $this->status_id,
            'priority' => $this->priority,
            'priority_display' => $this->priority_display,
            'priority_color' => $this->priority_color,
            'created_by' => $this->created_by,
            'assigned_to' => $this->assigned_to,
            'estimated_cost' => $this->estimated_cost,
            'actual_cost' => $this->actual_cost,
            'cost_variance' => $this->cost_variance,
            'estimated_completion' => $this->estimated_completion?->toDateString(),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Status helpers
            'is_started' => $this->isStarted(),
            'is_completed' => $this->isCompleted(),
            'is_overdue' => $this->isOverdue(),
            'duration_days' => $this->duration_days,

            // Related data
            'kart' => new KartResource($this->whenLoaded('kart')),
            'status' => new RequestStatusResource($this->whenLoaded('status')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'assigned_mechanic' => new UserResource($this->whenLoaded('assignedMechanic')),

            // Additional computed fields for UI
            'progress_info' => [
                'status' => $this->getProgressStatus(),
                'percentage' => $this->getProgressPercentage(),
                'days_since_creation' => $this->created_at->diffInDays(now()),
                'days_until_deadline' => $this->estimated_completion ? $this->estimated_completion->diffInDays(now(), false) : null,
            ],

            'cost_info' => [
                'estimated' => (float) $this->estimated_cost,
                'actual' => (float) $this->actual_cost,
                'variance' => $this->cost_variance,
                'variance_percentage' => $this->estimated_cost > 0 ? round(($this->cost_variance / $this->estimated_cost) * 100, 2) : 0,
                'is_over_budget' => $this->cost_variance > 0,
            ],
        ];
    }

    /**
     * Get the progress status for UI.
     */
    private function getProgressStatus(): string
    {
        if ($this->isCompleted()) {
            return 'completed';
        }

        if ($this->isStarted()) {
            return $this->isOverdue() ? 'overdue' : 'in_progress';
        }

        return 'pending';
    }

    /**
     * Get the progress percentage for UI.
     */
    private function getProgressPercentage(): int
    {
        if ($this->isCompleted()) {
            return 100;
        }

        if (!$this->isStarted()) {
            return 0;
        }

        if (!$this->estimated_completion || !$this->started_at) {
            return 50; // Default for started but no timeline
        }

        $totalDays = $this->started_at->diffInDays($this->estimated_completion);
        if ($totalDays <= 0) {
            return 80; // Almost done if timeline is very short
        }

        $elapsedDays = $this->started_at->diffInDays(now());
        $percentage = min(90, max(10, ($elapsedDays / $totalDays) * 100));

        return (int) round($percentage);
    }
}
