<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User Model
 * Central entity for all three roles: freelancer, mentor, admin.
 * Uses UserRole enum for type-safe role checking.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'avatar',
        'bio',
        'location',
        'timezone',
        'average_rating',
        'total_reviews',
        'is_active',
        'account_status',
        'rejection_reason',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => UserRole::class,
            'average_rating'    => 'decimal:1',
            'total_reviews'     => 'integer',
            'is_active'         => 'boolean',
            'account_status'    => 'string',
        ];
    }

    /* ─── Accessors ─── */

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        // Fallback: UI Avatars with initials
        $name = urlencode($this->first_name . ' ' . $this->last_name);
        return "https://ui-avatars.com/api/?name={$name}&size=200&background=4f46e5&color=fff&bold=true";
    }

    /* ─── Role Checks ─── */

    public function isFreelancer(): bool
    {
        return $this->role === UserRole::FREELANCER;
    }

    public function isMentor(): bool
    {
        return $this->role === UserRole::MENTOR;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /* ─── Relationships ─── */

    public function mentorProfile(): HasOne
    {
        return $this->hasOne(MentorProfile::class);
    }

    public function gigs(): HasMany
    {
        return $this->hasMany(Gig::class, 'mentor_id');
    }

    public function bookingsAsFreelancer(): HasMany
    {
        return $this->hasMany(Booking::class, 'freelancer_id');
    }

    public function bookingsAsMentor(): HasMany
    {
        return $this->hasMany(Booking::class, 'mentor_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'freelancer_id');
    }

    public function writtenReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function receivedReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function availabilitySlots(): HasMany
    {
        return $this->hasMany(AvailabilitySlot::class, 'mentor_id');
    }

    /* ─── LMS Relationships ─── */

    public function mentorshipRelationshipsAsMentor(): HasMany
    {
        return $this->hasMany(MentorshipRelationship::class, 'mentor_id');
    }

    public function mentorshipRelationshipsAsFreelancer(): HasMany
    {
        return $this->hasMany(MentorshipRelationship::class, 'freelancer_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'freelancer_id');
    }

    /* ─── Scopes ─── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, UserRole $role)
    {
        return $query->where('role', $role);
    }

    public function scopeVerifiedMentors($query)
    {
        return $query->where('role', UserRole::MENTOR)
            ->whereHas('mentorProfile', function ($q) {
                $q->where('verification_status', 'verified');
            });
    }

    public function scopeSearchByName($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%");
        });
    }
}
