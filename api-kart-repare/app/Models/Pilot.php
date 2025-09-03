<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pilot extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'size_tshirt',
        'size_pants',
        'size_shoes',
        'size_glove',
        'size_suit',
        'phone',
        'email',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'is_minor',
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
            'date_of_birth' => 'date',
            'is_minor' => 'boolean',
        ];
    }

    /**
     * Get the pilot's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the client that owns the pilot.
     */
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Check if pilot is a minor.
     */
    public function isMinor(): bool
    {
        return $this->is_minor ||
               ($this->date_of_birth && $this->date_of_birth->diffInYears(now()) < 18);
    }

    /**
     * Get the pilot's age.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->diffInYears(now()) : null;
    }

    /**
     * Scope a query to only include minors.
     */
    public function scopeMinors($query)
    {
        return $query->where('is_minor', true);
    }

    /**
     * Scope a query to only include adults.
     */
    public function scopeAdults($query)
    {
        return $query->where('is_minor', false);
    }

    /**
     * Scope a query to filter by client.
     */
    public function scopeOfClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }
}
