<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'ref',
        'price',
        'image',
        'in_stock',
        'unity',
        'min_stock',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'in_stock' => 'integer',
        'min_stock' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'unity_display',
        'stock_status',
        'needs_restock',
        'stock_percentage',
    ];

    // Computed Attributes
    public function getUnityDisplayAttribute()
    {
        return match($this->unity) {
            'piece' => 'Pièce(s)',
            'hours' => 'Heure(s)',
            'liters' => 'Litre(s)',
            'kg' => 'Kilogramme(s)',
            default => 'Non défini',
        };
    }

    public function getStockStatusAttribute()
    {
        if ($this->in_stock <= 0) {
            return 'out_of_stock';
        } elseif ($this->in_stock <= $this->min_stock) {
            return 'low_stock';
        } elseif ($this->in_stock <= ($this->min_stock * 2)) {
            return 'medium_stock';
        } else {
            return 'high_stock';
        }
    }

    public function getNeedsRestockAttribute()
    {
        return $this->in_stock <= $this->min_stock;
    }

    public function getStockPercentageAttribute()
    {
        if ($this->min_stock == 0) {
            return $this->in_stock > 0 ? 100 : 0;
        }
        return min(100, ($this->in_stock / ($this->min_stock * 3)) * 100);
    }

    // Business Logic Methods
    public function isAvailable(): bool
    {
        return $this->in_stock > 0;
    }

    public function isOutOfStock(): bool
    {
        return $this->in_stock <= 0;
    }

    public function needsRestock(): bool
    {
        return $this->in_stock <= $this->min_stock;
    }

    public function reduceStock(int $quantity): bool
    {
        if ($this->in_stock >= $quantity) {
            $this->decrement('in_stock', $quantity);
            return true;
        }
        return false;
    }

    public function addStock(int $quantity): void
    {
        $this->increment('in_stock', $quantity);
    }

    public function getStockColor(): string
    {
        return match($this->stock_status) {
            'out_of_stock' => '#dc3545',
            'low_stock' => '#fd7e14',
            'medium_stock' => '#ffc107',
            'high_stock' => '#28a745',
            default => '#6c757d',
        };
    }

    // Scopes
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('in_stock', '>', 0);
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('in_stock', '<=', 0);
    }

    public function scopeNeedsRestock(Builder $query): Builder
    {
        return $query->whereRaw('in_stock <= min_stock');
    }

    public function scopeByUnity(Builder $query, string $unity): Builder
    {
        return $query->where('unity', $unity);
    }

    public function scopeWithMinPrice(Builder $query, float $minPrice): Builder
    {
        return $query->where('price', '>=', $minPrice);
    }

    public function scopeWithMaxPrice(Builder $query, float $maxPrice): Builder
    {
        return $query->where('price', '<=', $maxPrice);
    }

    public function scopeSearchByName(Builder $query, string $search): Builder
    {
        return $query->where('name', 'LIKE', "%{$search}%");
    }

    public function scopeSearchByRef(Builder $query, string $search): Builder
    {
        return $query->where('ref', 'LIKE', "%{$search}%");
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('ref', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }
}
