<?php

namespace App\Enums;

enum RelationshipStatus: string
{
    case PENDING  = 'pending';
    case ACCEPTED = 'accepted';
    case DECLINED = 'declined';
    case ENDED    = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::PENDING  => 'Pending',
            self::ACCEPTED => 'Active',
            self::DECLINED => 'Declined',
            self::ENDED    => 'Ended',
        };
    }

    public function colorClass(): string
    {
        return match ($this) {
            self::PENDING  => 'warning',
            self::ACCEPTED => 'success',
            self::DECLINED => 'error',
            self::ENDED    => 'default',
        };
    }

    public function canTransitionTo(self $new): bool
    {
        return match ($this) {
            self::PENDING  => in_array($new, [self::ACCEPTED, self::DECLINED]),
            self::ACCEPTED => $new === self::ENDED,
            default        => false,
        };
    }
}
