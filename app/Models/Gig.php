<?php

namespace App\Models;

use App\Enums\GigStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Gig Model
 * Represents a mentor's service offering (mentoring session).
 * Includes full-text search, price filtering, and skill-based filtering.
 */
class Gig extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'mentor_id',
        'title',
        'slug',
        'description',
        'what_to_expect',
        'prerequisites',
        'delivery_format',
        'experience_level',
        'duration_minutes',
        'price',
        'status',
        'max_sessions_per_week',
        'booking_lead_time_hours',
    ];

    protected $casts = [
        'status' => GigStatus::class,
        'price' => 'decimal:2',
        'average_rating' => 'decimal:1',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($gig) {
            if (empty($gig->slug)) {
                $gig->slug = Str::slug($gig->title) . '-' . uniqid();
            }
        });
    }

    /* ─── Relationships ─── */

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /* ─── Accessors ─── */

    public function getFormattedPriceAttribute(): string
    {
        return 'Rs ' . number_format($this->price, 2);
    }

    public function getFormattedDurationAttribute(): string
    {
        return $this->duration_minutes >= 60
            ? floor($this->duration_minutes / 60) . 'h ' . ($this->duration_minutes % 60) . 'm'
            : $this->duration_minutes . ' min';
    }

    /* ─── Scopes ─── */

    public function scopePublished($query)
    {
        return $query->where('status', GigStatus::PUBLISHED);
    }

    public function scopeByMentor($query, int $mentorId)
    {
        return $query->where('mentor_id', $mentorId);
    }

    public function scopeByExperienceLevel($query, string $level)
    {
        return $query->where('experience_level', $level);
    }

    public function scopeBySkills($query, array $skillIds)
    {
        return $query->whereHas('skills', function ($q) use ($skillIds) {
            $q->whereIn('skills.id', $skillIds);
        });
    }

    public function scopePriceRange($query, ?float $min, ?float $max)
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price', '<=', $max);
        }
        return $query;
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->whereFullText(['title', 'description'], $term)
                ->orWhere('title', 'like', "%{$term}%");
        });
    }

    public function scopeWithMentorRating($query)
    {
        return $query->with(['mentor.mentorProfile']);
    }

    public function scopeOrderByRating($query, string $direction = 'desc')
    {
        return $query->orderBy('average_rating', $direction);
    }

    public function scopeOrderByPrice($query, string $direction = 'asc')
    {
        return $query->orderBy('price', $direction);
    }

    public function scopeOrderByPopularity($query)
    {
        return $query->orderBy('total_bookings', 'desc');
    }
}
