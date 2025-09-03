<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequestStatus extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'hex_color',
        'is_final',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_final' => 'boolean',
        ];
    }

    /**
     * Check if this status is final (no more transitions possible).
     */
    public function isFinal(): bool
    {
        return $this->is_final;
    }

    /**
     * Get the status display name with color information.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get the status color information.
     */
    public function getColorInfoAttribute(): array
    {
        return [
            'hex' => $this->hex_color,
            'name' => $this->name,
        ];
    }

    /**
     * Scope a query to only include final statuses.
     */
    public function scopeFinal($query)
    {
        return $query->where('is_final', true);
    }

    /**
     * Scope a query to only include non-final statuses.
     */
    public function scopeNotFinal($query)
    {
        return $query->where('is_final', false);
    }

    /**
     * Scope a query to filter by color.
     */
    public function scopeWithColor($query, string $hexColor)
    {
        return $query->where('hex_color', $hexColor);
    }

    /**
     * Get a valid hex color or default.
     */
    public function getValidHexColorAttribute(): string
    {
        // Validate hex color format
        if (preg_match('/^#[a-fA-F0-9]{6}$/', $this->hex_color)) {
            return $this->hex_color;
        }

        // Return default color if invalid
        return '#6c757d'; // Bootstrap secondary color
    }
}
