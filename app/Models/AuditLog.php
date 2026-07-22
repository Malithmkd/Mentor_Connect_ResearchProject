<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * AuditLog Model
 * Records all system activities for administrative review.
 */
class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'description',
        'area',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    /* ─── Relationships ─── */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /* ─── Scopes ─── */

    public function scopeByArea($query, string $area)
    {
        return $query->where('area', $area);
    }

    public function scopeByEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('description', 'like', "%{$term}%")
              ->orWhere('event', 'like', "%{$term}%");
        });
    }

    /* ─── Helpers ─── */

    /**
     * Log an audit event.
     */
    public static function log(
        string $event,
        string $description,
        string $area = 'system',
        ?object $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): self {
        return static::create([
            'user_id'        => auth()->id(),
            'event'          => $event,
            'description'    => $description,
            'area'           => $area,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id'   => $auditable?->id,
            'old_values'     => $oldValues,
            'new_values'     => $newValues,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
        ]);
    }

    /**
     * Icon class for each area.
     */
    public function areaIcon(): string
    {
        return match ($this->area) {
            'auth'      => 'icon-auth',
            'users'     => 'icon-users',
            'approvals' => 'icon-approvals',
            'bookings'  => 'icon-bookings',
            'gigs'      => 'icon-gigs',
            'lms'       => 'icon-lms',
            default     => 'icon-system',
        };
    }

    /**
     * Color class for the badge.
     */
    public function areaColor(): string
    {
        return match ($this->area) {
            'auth'      => 'blue',
            'users'     => 'purple',
            'approvals' => 'amber',
            'bookings'  => 'green',
            'gigs'      => 'info',
            'lms'       => 'purple',
            default     => 'neutral',
        };
    }
}
