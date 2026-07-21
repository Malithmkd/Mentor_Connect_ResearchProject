<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Enrollment Model
 * Links a freelancer to a course within a mentorship relationship.
 * Created automatically when the mentor publishes a course.
 * completed_at is set when all lessons are marked done.
 */
class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'relationship_id',
        'freelancer_id',
        'enrolled_at',
        'completed_at',
    ];

    protected $casts = [
        'enrolled_at'  => 'datetime',
        'completed_at' => 'datetime',
    ];

    /* ─── Relationships ─── */

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function relationship(): BelongsTo
    {
        return $this->belongsTo(MentorshipRelationship::class, 'relationship_id');
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /* ─── Computed Accessors ─── */

    /**
     * Progress percentage: how many lessons of this course are completed.
     * Returns integer 0–100.
     */
    public function getProgressPercentageAttribute(): int
    {
        $totalLessons = $this->course->lessons()->count();

        if ($totalLessons === 0) {
            return 0;
        }

        $completedLessons = $this->lessonProgress()
            ->whereNotNull('completed_at')
            ->count();

        return (int) round(($completedLessons / $totalLessons) * 100);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /* ─── Scopes ─── */

    public function scopeForFreelancer($query, int $freelancerId)
    {
        return $query->where('freelancer_id', $freelancerId);
    }
}
