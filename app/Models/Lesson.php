<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lesson Model
 * Individual content unit within a CourseModule.
 * content is stored as longText (raw HTML/Markdown rendered in the view).
 * video_url is optional — embedded as an <iframe> if present.
 */
class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'title',
        'content',
        'video_url',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /* ─── Relationships ─── */

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /* ─── Helpers ─── */

    /**
     * Convert a standard YouTube or Vimeo URL to an embeddable iframe src.
     * Handles all common YouTube formats:
     *   https://www.youtube.com/watch?v=ID
     *   https://youtu.be/ID
     *   https://youtube.com/shorts/ID
     *   https://www.youtube.com/embed/ID  (already correct — pass through)
     * And Vimeo:
     *   https://vimeo.com/ID
     *   https://player.vimeo.com/video/ID (already correct — pass through)
     */
    public function getEmbedUrl(): ?string
    {
        if (!$this->video_url) {
            return null;
        }

        $url = trim($this->video_url);

        // ── YouTube ──────────────────────────────────────────────────────────
        // Already an embed URL
        if (str_contains($url, 'youtube.com/embed/')) {
            return $url;
        }

        // youtu.be/ID
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_\-]{11})/', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        // youtube.com/watch?v=ID  or  youtube.com/shorts/ID
        if (preg_match('/youtube\.com\/(?:watch\?v=|shorts\/)([a-zA-Z0-9_\-]{11})/', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        // ── Vimeo ────────────────────────────────────────────────────────────
        if (str_contains($url, 'player.vimeo.com/video/')) {
            return $url;
        }

        if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        // Unknown platform — return as-is and let the browser try
        return $url;
    }

    /** Check if a specific freelancer has completed this lesson */
    public function isCompletedBy(int $freelancerId): bool
    {
        return $this->progress()
            ->where('freelancer_id', $freelancerId)
            ->whereNotNull('completed_at')
            ->exists();
    }

    /* ─── Scopes ─── */

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
