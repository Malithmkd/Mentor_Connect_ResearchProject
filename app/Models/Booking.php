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
            BookingStatus::SCHEDULED => $this->scheduled_at = now(),
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
        return '$' . number_format($this->price_paid, 2);
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
