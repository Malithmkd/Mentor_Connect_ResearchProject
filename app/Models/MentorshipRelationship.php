<?php

namespace App\Models;

use App\Enums\RelationshipStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * MentorshipRelationship Model
 * Represents a long-term engagement between a mentor and a freelancer,
 * initiated after a completed booking session.
 * Stores agreed payment terms as plain data (no gateway).
 */
class MentorshipRelationship extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_id',
        'mentor_id',
        'freelancer_id',
        'status',
        'payment_type',
        'payment_amount',
        'payment_notes',
        'requested_at',
        'accepted_at',
        'ended_at',
    ];

    protected $casts = [
        'status'         => RelationshipStatus::class,
        'payment_amount' => 'decimal:2',
        'requested_at'   => 'datetime',
        'accepted_at'    => 'datetime',
        'ended_at'       => 'datetime',
    ];

    /* ─── Relationships ─── */

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'relationship_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'relationship_id');
    }

    /* ─── Status Helpers ─── */

    public function isPending(): bool
    {
        return $this->status === RelationshipStatus::PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === RelationshipStatus::ACCEPTED;
    }

    public function isDeclined(): bool
    {
        return $this->status === RelationshipStatus::DECLINED;
    }

    public function isEnded(): bool
    {
        return $this->status === RelationshipStatus::ENDED;
    }

    /* ─── Scopes ─── */

    public function scopeForMentor($query, int $mentorId)
    {
        return $query->where('mentor_id', $mentorId);
    }

    public function scopeForFreelancer($query, int $freelancerId)
    {
        return $query->where('freelancer_id', $freelancerId);
    }

    public function scopePending($query)
    {
        return $query->where('status', RelationshipStatus::PENDING);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', RelationshipStatus::ACCEPTED);
    }
}
