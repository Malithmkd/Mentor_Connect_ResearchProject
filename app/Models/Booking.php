<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Booking Model
 * Core of the session booking state machine.
 * All status transitions are validated via BookingStatus enum.
 * Observer pattern updates mentor stats on status change.
 */
class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bookings';

    protected $fillable = [
        'freelancer_id',
        'mentor_id',
        'gig_id',
        'booking_reference',
        'status',
        'requested_at',
        'responded_at',
        'scheduled_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'freelancer_note',
        'mentor_note',
        'proposed_date',
        'price_paid',
        'meeting_link',
        'meeting_provider',
        'proposed_time',
    ];

    protected $casts = [
        'status' => BookingStatus::class,
        'price_paid' => 'decimal:2',
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'proposed_date' => 'date',
        // proposed_time is stored as a plain string (HH:MM) — Carbon handles it below
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_reference)) {
                $booking->booking_reference = 'MC-' . strtoupper(uniqid());
            }
            if ($booking->status === BookingStatus::REQUESTED && empty($booking->requested_at)) {
                $booking->requested_at = now();
            }
        });
    }

    /* ─── Relationships ─── */

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function gig(): BelongsTo
    {
        return $this->belongsTo(Gig::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function mentorshipRelationship(): HasOne
    {
        return $this->hasOne(MentorshipRelationship::class);
    }

    public function freelancerReview(): HasOne
    {
        return $this->hasOne(Review::class)->whereColumn('reviews.reviewer_id', 'reviews.freelancer_id');
    }

    public function mentorReview(): HasOne
    {
        return $this->hasOne(Review::class)->whereColumn('reviews.reviewer_id', 'reviews.mentor_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(BookingNote::class)->oldest();
    }

    /* ─── State Machine Methods ─── */

    public function transitionTo(BookingStatus $newStatus, ?string $note = null): bool
    {
        $current = $this->status;

        if (!$current->canTransitionTo($newStatus)) {
            return false;
        }

        $this->status = $newStatus;

        match ($newStatus) {
            BookingStatus::REQUESTED => $this->requested_at = now(),
            BookingStatus::ACCEPTED, BookingStatus::REJECTED => $this->responded_at = now(),
            BookingStatus::SCHEDULED => $this->scheduled_at = $this->sessionStartDateTime() ?? now(),
            BookingStatus::COMPLETED => $this->completed_at = now(),
            BookingStatus::CANCELLED => $this->cancelled_at = now(),
            default => null,
        };

        if ($note) {
            match ($newStatus) {
                BookingStatus::REJECTED => $this->mentor_note = $note,
                BookingStatus::CANCELLED => $this->cancellation_reason = $note,
                default => null,
            };
        }

        return $this->save();
    }

    public function canBeReviewed(): bool
    {
        return $this->status->canBeReviewed();
    }

    /**
     * Returns a Carbon datetime representing when the session ends:
     * proposed_date + proposed_time + gig.duration_minutes.
     * Returns null if either proposed_date or proposed_time is missing.
     */
    public function sessionEndDateTime(): ?\Carbon\Carbon
    {
        if (!$this->proposed_date || !$this->proposed_time) {
            return null;
        }

        $start = \Carbon\Carbon::parse(
            $this->proposed_date->format('Y-m-d') . ' ' . $this->proposed_time
        );

        $durationMinutes = $this->gig?->duration_minutes ?? 0;

        return $start->addMinutes($durationMinutes);
    }

    /**
     * Returns the session start Carbon datetime, or null if date/time not provided.
     */
    public function sessionStartDateTime(): ?\Carbon\Carbon
    {
        if (!$this->proposed_date || !$this->proposed_time) {
            return null;
        }

        return \Carbon\Carbon::parse(
            $this->proposed_date->format('Y-m-d') . ' ' . $this->proposed_time
        );
    }

    /**
     * A booking request has "expired" for acceptance if the proposed
     * start date+time has already passed.
     * Bookings with no proposed_time are never considered expired.
     */
    public function isAcceptanceExpired(): bool
    {
        $start = $this->sessionStartDateTime();

        return $start !== null && $start->isPast();
    }

    /**
     * The mentor can only mark a session as completed once its
     * end time (start + duration) has passed.
     * If no time was set, there is no time restriction.
     */
    public function canBeMarkedComplete(): bool
    {
        $end = $this->sessionEndDateTime();

        // No time restriction for bookings without proposed_time
        if ($end === null) {
            return true;
        }

        return $end->isPast();
    }

    public function canBeReviewedBy(?User $user = null): bool
    {
        if (!$user || !$this->status->canBeReviewed()) {
            return false;
        }

        if ($user->id !== $this->freelancer_id && $user->id !== $this->mentor_id) {
            return false;
        }

        return !$this->reviews()->where('reviewer_id', $user->id)->exists();
    }

    /* ─── Accessors ─── */

    public function getFormattedPriceAttribute(): string
    {
        return 'Rs ' . number_format($this->price_paid, 2);
    }

    /* ─── Scopes ─── */

    public function scopeByFreelancer($query, int $freelancerId)
    {
        return $query->where('freelancer_id', $freelancerId);
    }

    public function scopeByMentor($query, int $mentorId)
    {
        return $query->where('mentor_id', $mentorId);
    }

    public function scopeByStatus($query, BookingStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            BookingStatus::REJECTED,
            BookingStatus::CANCELLED,
            BookingStatus::REVIEWED,
        ]);
    }

    public function scopePendingResponse($query)
    {
        return $query->where('status', BookingStatus::REQUESTED);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', BookingStatus::SCHEDULED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', BookingStatus::COMPLETED);
    }

    public function scopeAwaitingReview($query, ?int $userId = null)
    {
        $userId = $userId ?? (auth()->check() ? auth()->id() : null);

        $query->whereIn('status', [BookingStatus::COMPLETED, BookingStatus::REVIEWED]);

        if ($userId) {
            return $query->whereDoesntHave('reviews', function ($q) use ($userId) {
                $q->where('reviewer_id', $userId);
            });
        }

        return $query->whereDoesntHave('reviews');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
