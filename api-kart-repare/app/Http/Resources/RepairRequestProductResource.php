<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepairRequestProductResource extends JsonResource
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
            'repair_request_id' => $this->repair_request_id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'priority' => $this->priority,
            'note' => $this->note,

            // Workflow information
            'status' => $this->status,
            'is_invoiced' => $this->is_invoiced,
            'is_completed' => $this->is_completed,
            'is_approved' => $this->is_approved,
            'can_be_invoiced' => $this->canBeInvoiced(),
            'can_be_completed' => $this->canBeCompleted(),
            'can_be_approved' => $this->canBeApproved(),

            // Timestamps
            'invoiced_at' => $this->invoiced_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),

            // Related data
            'repair_request' => $this->whenLoaded('repairRequest', function () {
                return [
                    'id' => $this->repairRequest->id,
                    'reference' => $this->repairRequest->reference,
                    'status' => $this->repairRequest->status,
                    'priority' => $this->repairRequest->priority,
                    'description' => $this->repairRequest->description,
                    'created_at' => $this->repairRequest->created_at->format('Y-m-d H:i:s'),
                ];
            }),

            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'reference' => $this->product->reference,
                    'category' => $this->product->category,
                    'brand' => $this->product->brand,
                    'model' => $this->product->model,
                    'price' => $this->product->price,
                    'stock_quantity' => $this->product->stock_quantity,
                    'is_active' => $this->product->is_active,
                ];
            }),

            'invoiced_by_user' => $this->whenLoaded('invoicedBy', function () {
                return [
                    'id' => $this->invoicedBy->id,
                    'name' => $this->invoicedBy->name,
                    'email' => $this->invoicedBy->email,
                ];
            }),

            'completed_by_user' => $this->whenLoaded('completedBy', function () {
                return [
                    'id' => $this->completedBy->id,
                    'name' => $this->completedBy->name,
                    'email' => $this->completedBy->email,
                ];
            }),

            // Computed fields
            'days_since_created' => $this->created_at->diffInDays(now()),
            'days_to_complete' => $this->when($this->is_completed && $this->completed_at, function () {
                return $this->created_at->diffInDays($this->completed_at);
            }),
            'total_cost_formatted' => number_format($this->total_price, 2, ',', ' ') . ' €',

            // Workflow progress
            'workflow_progress' => $this->getWorkflowProgress(),
            'workflow_next_step' => $this->getNextWorkflowStep(),
        ];
    }

    /**
     * Get workflow progress as percentage.
     *
     * @return int
     */
    protected function getWorkflowProgress(): int
    {
        if ($this->is_approved) {
            return 100;
        }

        if ($this->is_completed) {
            return 75;
        }

        if ($this->is_invoiced) {
            return 50;
        }

        return 25; // Created but not invoiced
    }

    /**
     * Get the next step in the workflow.
     *
     * @return string|null
     */
    protected function getNextWorkflowStep(): ?string
    {
        if ($this->is_approved) {
            return null; // Workflow complete
        }

        if ($this->is_completed) {
            return 'approve';
        }

        if ($this->is_invoiced) {
            return 'complete';
        }

        return 'invoice'; // First step
    }
}
