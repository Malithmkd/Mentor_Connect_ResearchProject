<?php

namespace App\Enums;

/**
 * User role enum for RBAC.
 * Defines the three roles in MentorConnect.
 */
enum UserRole: string
{
    case FREELANCER = 'freelancer';
    case MENTOR = 'mentor';
    case ADMIN = 'admin';

    /**
     * Human-readable labels for UI display.
     */
    public function label(): string
    {
        return match ($this) {
            self::FREELANCER => 'Freelancer',
            self::MENTOR => 'Mentor',
            self::ADMIN => 'Administrator',
        };
    }

    /**
     * Check if role can access mentor-specific features.
     */
    public function canBeMentor(): bool
    {
        return in_array($this, [self::MENTOR, self::ADMIN], true);
    }

    /**
     * Check if role has admin privileges.
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * Get all values for form selects.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
