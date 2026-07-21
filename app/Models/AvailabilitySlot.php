<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AvailabilitySlot Model
 * Weekly recurring availability for mentors.
 * Used for scheduling and conflict detection.
 */
class AvailabilitySlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'mentor_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_available',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_available' => 'boolean',
    ];

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    /* ─── Scopes ─── */

    public function scopeForMentor($query, int $mentorId)
    {
        return $query->where('mentor_id', $mentorId);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeByDay($query, string $day)
    {
        return $query->where('day_of_week', $day);
    }
}
