<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Review Model
 * Post-session feedback from freelancers to mentors.
 * Triggers recalculation of mentor and gig ratings via observer.
 */
class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'reviewer_id',
        'reviewee_id',
        'freelancer_id',
        'mentor_id',
        'gig_id',
        'rating',
        'comment',
        'is_public',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_public' => 'boolean',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

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

    /* ─── Scopes ─── */

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByReviewer($query, int $userId)
    {
        return $query->where('reviewer_id', $userId);
    }

    public function scopeByReviewee($query, int $userId)
    {
        return $query->where('reviewee_id', $userId);
    }

    public function scopeByMentor($query, int $mentorId)
    {
        return $query->where(function ($q) use ($mentorId) {
            $q->where('reviewee_id', $mentorId)
              ->orWhere(function ($q2) use ($mentorId) {
                  $q2->whereNull('reviewee_id')->where('mentor_id', $mentorId);
              });
        });
    }

    public function scopeByGig($query, int $gigId)
    {
        return $query->where('gig_id', $gigId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
