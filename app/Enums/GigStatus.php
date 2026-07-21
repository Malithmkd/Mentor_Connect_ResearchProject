<?php

namespace App\Enums;

enum GigStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case PAUSED = 'paused';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::PAUSED => 'Paused',
            self::ARCHIVED => 'Archived',
        };
    }

    public function colorClass(): string
    {
        return match ($this) {
            self::DRAFT => 'badge--neutral',
            self::PUBLISHED => 'badge--success',
            self::PAUSED => 'badge--warning',
            self::ARCHIVED => 'badge--error',
        };
    }

    /**
     * Only published gigs are visible to freelancers.
     */
    public function isPublic(): bool
    {
        return $this === self::PUBLISHED;
    }
}
