<?php

namespace App\Models;

use App\Enums\CourseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Course Model
 * A course is private to one MentorshipRelationship.
 * Publishing a course automatically enrolls the relationship's freelancer.
 */
class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'relationship_id',
        'mentor_id',
        'title',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => CourseStatus::class,
    ];

    /* ─── Relationships ─── */

    public function relationship(): BelongsTo
    {
        return $this->belongsTo(MentorshipRelationship::class, 'relationship_id');
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('sort_order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** All lessons across every module of this course */
    public function lessons(): HasManyThrough
    {
        // 3rd arg = FK on course_modules pointing to courses (course_id) ✓ default
        // 4th arg = FK on lessons pointing to course_modules — must be 'module_id',
        //           NOT the default guess of 'course_module_id'
        return $this->hasManyThrough(
            Lesson::class,
            CourseModule::class,
            'course_id',  // FK on course_modules → courses
            'module_id',  // FK on lessons → course_modules
        );
    }

    /* ─── Status Helpers ─── */

    public function isDraft(): bool
    {
        return $this->status === CourseStatus::DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === CourseStatus::PUBLISHED;
    }

    /* ─── Scopes ─── */

    public function scopePublished($query)
    {
        return $query->where('status', CourseStatus::PUBLISHED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', CourseStatus::DRAFT);
    }
}
