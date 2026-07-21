<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CourseModule Model
 * An ordered section within a Course, containing multiple Lessons.
 */
class CourseModule extends Model
{
    use HasFactory;

    protected $table = 'course_modules';

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /* ─── Relationships ─── */

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'module_id')->orderBy('sort_order');
    }

    /* ─── Scopes ─── */

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
