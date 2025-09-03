<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'ref' => $this->ref,
            'price' => (float) $this->price,
            'price_formatted' => number_format($this->price, 2) . ' €',
            'image' => $this->image,
            'in_stock' => $this->in_stock,
            'unity' => $this->unity,
            'unity_display' => $this->unity_display,
            'min_stock' => $this->min_stock,
            'stock_status' => $this->stock_status,
            'needs_restock' => $this->needs_restock,
            'stock_percentage' => round($this->stock_percentage, 1),
            'stock_color' => $this->getStockColor(),
            'is_available' => $this->isAvailable(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,

            // Métriques supplémentaires
            'stock_value' => round($this->price * $this->in_stock, 2),
            'stock_value_formatted' => number_format($this->price * $this->in_stock, 2) . ' €',

            // Calculs utiles pour la gestion
            'stock_difference' => $this->in_stock - $this->min_stock,
            'restock_suggested' => $this->min_stock * 2, // Suggestion de réapprovisionnement
        ];
    }
}
