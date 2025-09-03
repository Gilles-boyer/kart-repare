<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class RepairRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'kart_id',
        'title',
        'description',
        'status_id',
        'priority',
        'estimated_cost',
        'actual_cost',
        'estimated_completion',
        'started_at',
        'completed_at',
        'created_by',
        'assigned_to',
        'notes',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'estimated_completion' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'priority_display',
        'priority_color',
        'cost_variance',
        'duration_days',
    ];

    // Relations
    public function kart()
    {
        return $this->belongsTo(Kart::class);
    }

    public function status()
    {
        return $this->belongsTo(RequestStatus::class, 'status_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Computed Attributes
    public function getPriorityDisplayAttribute()
    {
        return match($this->priority) {
            'low' => 'Basse',
            'medium' => 'Moyenne',
            'high' => 'Haute',
            default => 'Non définie',
        };
    }

    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            'low' => '#28a745',
            'medium' => '#ffc107',
            'high' => '#dc3545',
            default => '#6c757d',
        };
    }

    public function getCostVarianceAttribute()
    {
        if (!$this->actual_cost || !$this->estimated_cost) {
            return null;
        }
        return $this->actual_cost - $this->estimated_cost;
    }

    public function getDurationDaysAttribute()
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }
        return $this->started_at->diffInDays($this->completed_at);
    }

    // Business Logic Methods
    public function isCompleted(): bool
    {
        return !is_null($this->completed_at);
    }

    public function isStarted(): bool
    {
        return !is_null($this->started_at);
    }

    public function isOverdue(): bool
    {
        if ($this->isCompleted() || !$this->estimated_completion) {
            return false;
        }
        return Carbon::parse($this->estimated_completion)->isPast();
    }

    public function canBeUpdated(): bool
    {
        return !$this->isStarted();
    }

    public function canBeDeleted(): bool
    {
        return !$this->isStarted();
    }

    public function canBeStarted(): bool
    {
        return !$this->isStarted() && !$this->isCompleted();
    }

    public function canBeCompleted(): bool
    {
        return $this->isStarted() && !$this->isCompleted();
    }

    // Scopes
    public function scopeWithPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function scopeForKart(Builder $query, int $kartId): Builder
    {
        return $query->where('kart_id', $kartId);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('estimated_completion')
                    ->whereNull('completed_at')
                    ->where('estimated_completion', '<', now());
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    public function scopeStarted(Builder $query): Builder
    {
        return $query->whereNotNull('started_at');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('started_at');
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeCreatedBy(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId);
    }

    public function scopeWithStatus(Builder $query, int $statusId): Builder
    {
        return $query->where('status_id', $statusId);
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
}
