<?php

namespace App\Enums;

/**
 * Session booking lifecycle states.
 * All transitions are validated in the BookingController state machine.
 */
enum BookingStatus: string
{
    case DRAFT = 'draft';
    case REQUESTED = 'requested';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case SCHEDULED = 'scheduled';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case REVIEWED = 'reviewed';

    /**
     * Human-readable status labels for UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::REQUESTED => 'Requested',
            self::ACCEPTED => 'Accepted',
            self::REJECTED => 'Rejected',
            self::SCHEDULED => 'Scheduled',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::REVIEWED => 'Reviewed',
        };
    }

    /**
     * UI color classes for status badges.
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::DRAFT => 'badge--neutral',
            self::REQUESTED => 'badge--warning',
            self::ACCEPTED => 'badge--success',
            self::REJECTED => 'badge--error',
            self::SCHEDULED => 'badge--info',
            self::COMPLETED => 'badge--success',
            self::CANCELLED => 'badge--error',
            self::REVIEWED => 'badge--purple',
        };
    }

    /**
     * Check if the status allows review submission.
     */
    public function canBeReviewed(): bool
    {
        return in_array($this, [self::COMPLETED, self::REVIEWED], true);
    }

    /**
     * Check if the status allows mentor response.
     */
    public function canRespond(): bool
    {
        return $this === self::REQUESTED;
    }

    /**
     * Check if the status can be cancelled.
     */
    public function canCancel(): bool
    {
        return in_array($this, [self::ACCEPTED, self::SCHEDULED], true);
    }

    /**
     * Check if the status is terminal (no further action).
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::REJECTED, self::CANCELLED, self::REVIEWED], true);
    }

    /**
     * Get valid next statuses for state machine validation.
     */
    public function validTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::REQUESTED],
            self::REQUESTED => [self::ACCEPTED, self::REJECTED],
            self::ACCEPTED => [self::SCHEDULED, self::CANCELLED],
            self::REJECTED => [],
            self::SCHEDULED => [self::COMPLETED, self::CANCELLED],
            self::COMPLETED => [self::REVIEWED],
            self::CANCELLED => [],
            self::REVIEWED => [],
        };
    }

    /**
     * Check if a transition to target status is valid.
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->validTransitions(), true);
    }
}
