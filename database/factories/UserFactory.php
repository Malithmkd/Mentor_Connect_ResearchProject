<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 *
 * Extended with role states, account_status states, and
 * first_name/last_name mapping required by the MentorConnect schema.
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /** The current password being used by the factory. */
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name'        => $this->faker->firstName(),
            'last_name'         => $this->faker->lastName(),
            'email'             => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'role'              => UserRole::FREELANCER,
            'account_status'    => 'approved',
            'is_active'         => true,
            'skills_onboarded'  => true,
            'average_rating'    => 0.0,
            'total_reviews'     => 0,
        ];
    }

    // ── Role States ──────────────────────────────────────────────────────────

    /** Freelancer user (default role) */
    public function freelancer(): static
    {
        return $this->state(fn () => ['role' => UserRole::FREELANCER]);
    }

    /** Mentor user */
    public function mentor(): static
    {
        return $this->state(fn () => ['role' => UserRole::MENTOR]);
    }

    /** Admin user */
    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::ADMIN]);
    }

    // ── Account Status States ─────────────────────────────────────────────────

    /** Pending admin approval */
    public function pending(): static
    {
        return $this->state(fn () => ['account_status' => 'pending']);
    }

    /** Approved account (default) */
    public function approved(): static
    {
        return $this->state(fn () => ['account_status' => 'approved']);
    }

    /** Rejected account */
    public function rejected(string $reason = 'Does not meet requirements.'): static
    {
        return $this->state(fn () => [
            'account_status'   => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    // ── Activity States ───────────────────────────────────────────────────────

    /** Disabled (is_active = false) */
    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /** Indicate that the model's email address should be unverified. */
    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    /** Has not completed skill onboarding */
    public function unonboarded(): static
    {
        return $this->state(fn () => ['skills_onboarded' => false]);
    }
}
