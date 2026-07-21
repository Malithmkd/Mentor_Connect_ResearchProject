<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MentorProfile Model
 * Extended profile information for users with mentor role.
 * One-to-one with User model.
 */
class MentorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'headline',
        'about',
        'company',
        'website',
        'linkedin_url',
        'github_url',
        'years_experience',
        'hourly_rate',
        'availability',
        'verification_status',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'average_rating' => 'decimal:1',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ─── Scopes ─── */

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function scopePending($query)
    {
        return $query->where('verification_status', 'pending');
    }
}
