<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class RepairRequestProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_request_id',
        'product_id',
        'quantity',
        'priority',
        'note',
        'unit_price',
        'total_price',
        'invoiced_by',
        'invoiced_at',
        'completed_by',
        'completed_at',
        'approved_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'invoiced_at' => 'datetime',
        'completed_at' => 'datetime',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'status',
        'priority_display',
        'priority_color',
        'is_invoiced',
        'is_completed',
        'is_approved',
        'days_since_creation',
    ];

    // Relations
    public function repairRequest()
    {
        return $this->belongsTo(RepairRequest::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function invoicedBy()
    {
        return $this->belongsTo(User::class, 'invoiced_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    // Computed Attributes
    public function getStatusAttribute(): string
    {
        if ($this->is_approved) {
            return 'approved';
        } elseif ($this->is_completed) {
            return 'completed';
        } elseif ($this->is_invoiced) {
            return 'invoiced';
        } else {
            return 'pending';
        }
    }

    public function getPriorityDisplayAttribute(): string
    {
        return match($this->priority) {
            'high' => 'Haute',
            'medium' => 'Moyenne',
            'low' => 'Basse',
            default => 'Non définie',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'high' => '#dc3545',
            'medium' => '#ffc107',
            'low' => '#28a745',
            default => '#6c757d',
        };
    }

    public function getIsInvoicedAttribute(): bool
    {
        return !is_null($this->invoiced_at);
    }

    public function getIsCompletedAttribute(): bool
    {
        return !is_null($this->completed_at);
    }

    public function getIsApprovedAttribute(): bool
    {
        return !is_null($this->approved_at);
    }

    public function getDaysSinceCreationAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    // Business Logic Methods
    public function canBeInvoiced(): bool
    {
        return !$this->is_invoiced && !$this->is_completed;
    }

    public function canBeCompleted(): bool
    {
        return $this->is_invoiced && !$this->is_completed;
    }

    public function canBeApproved(): bool
    {
        return $this->is_completed && !$this->is_approved;
    }

    public function canBeDeleted(): bool
    {
        return !$this->is_invoiced;
    }

    public function markAsInvoiced(User $user): bool
    {
        if (!$this->canBeInvoiced()) {
            return false;
        }

        $this->update([
            'invoiced_by' => $user->id,
            'invoiced_at' => now(),
        ]);

        return true;
    }

    public function markAsCompleted(User $user): bool
    {
        if (!$this->canBeCompleted()) {
            return false;
        }

        $this->update([
            'completed_by' => $user->id,
            'completed_at' => now(),
        ]);

        return true;
    }

    public function markAsApproved(): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->update([
            'approved_at' => now(),
        ]);

        return true;
    }

    public function calculateTotalPrice(): void
    {
        $this->total_price = $this->quantity * $this->unit_price;
        $this->save();
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'approved' => '#28a745',
            'completed' => '#17a2b8',
            'invoiced' => '#ffc107',
            'pending' => '#6c757d',
            default => '#6c757d',
        };
    }

    // Scopes
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('invoiced_at');
    }

    public function scopeInvoiced(Builder $query): Builder
    {
        return $query->whereNotNull('invoiced_at')
                    ->whereNull('completed_at');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at')
                    ->whereNull('approved_at');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->whereNotNull('approved_at');
    }

    public function scopeWithPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->where('priority', 'high');
    }

    public function scopeMediumPriority(Builder $query): Builder
    {
        return $query->where('priority', 'medium');
    }

    public function scopeLowPriority(Builder $query): Builder
    {
        return $query->where('priority', 'low');
    }

    public function scopeForRepairRequest(Builder $query, int $repairRequestId): Builder
    {
        return $query->where('repair_request_id', $repairRequestId);
    }

    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    public function scopeInvoicedBy(Builder $query, int $userId): Builder
    {
        return $query->where('invoiced_by', $userId);
    }

    public function scopeCompletedBy(Builder $query, int $userId): Builder
    {
        return $query->where('completed_by', $userId);
    }

    public function scopeInvoicedBetween(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('invoiced_at', [$startDate, $endDate]);
    }

    public function scopeCompletedBetween(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('completed_at', [$startDate, $endDate]);
    }

    public function scopeWithMinTotalPrice(Builder $query, float $minPrice): Builder
    {
        return $query->where('total_price', '>=', $minPrice);
    }

    public function scopeWithMaxTotalPrice(Builder $query, float $maxPrice): Builder
    {
        return $query->where('total_price', '<=', $maxPrice);
    }

    public function scopeWithQuantityRange(Builder $query, int $minQuantity, int $maxQuantity = null): Builder
    {
        $query->where('quantity', '>=', $minQuantity);

        if ($maxQuantity !== null) {
            $query->where('quantity', '<=', $maxQuantity);
        }

        return $query;
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($repairRequestProduct) {
            // Automatically calculate total price
            if ($repairRequestProduct->isDirty(['quantity', 'unit_price'])) {
                $repairRequestProduct->total_price = $repairRequestProduct->quantity * $repairRequestProduct->unit_price;
            }
        });
    }
}
