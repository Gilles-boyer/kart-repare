<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kart extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'pilot_id',
        'brand',
        'model',
        'chassis_number',
        'year',
        'engine_type',
        'is_active',
        'note',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the pilot that owns the kart.
     */
    public function pilot()
    {
        return $this->belongsTo(Pilot::class);
    }

    /**
     * Get the client through the pilot relationship.
     */
    public function client()
    {
        return $this->hasOneThrough(User::class, Pilot::class, 'id', 'id', 'pilot_id', 'client_id');
    }

    /**
     * Get the kart's full identification.
     */
    public function getFullIdentificationAttribute(): string
    {
        return "{$this->brand} {$this->model} ({$this->chassis_number})";
    }

    /**
     * Check if kart is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get the kart's age in years.
     */
    public function getAgeAttribute(): int
    {
        return now()->year - $this->year;
    }

    /**
     * Scope a query to only include active karts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive karts.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query to filter by pilot.
     */
    public function scopeOfPilot($query, int $pilotId)
    {
        return $query->where('pilot_id', $pilotId);
    }

    /**
     * Scope a query to filter by client through pilot relationship.
     */
    public function scopeOfClient($query, int $clientId)
    {
        return $query->whereHas('pilot', function ($q) use ($clientId) {
            $q->where('client_id', $clientId);
        });
    }

    /**
     * Scope a query to filter by brand.
     */
    public function scopeOfBrand($query, string $brand)
    {
        return $query->where('brand', $brand);
    }

    /**
     * Scope a query to filter by year range.
     */
    public function scopeOfYear($query, int $year)
    {
        return $query->where('year', $year);
    }
}
