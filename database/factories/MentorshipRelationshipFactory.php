<?php

namespace Database\Factories;

use App\Enums\RelationshipStatus;
use App\Models\Booking;
use App\Models\MentorshipRelationship;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MentorshipRelationship>
 */
class MentorshipRelationshipFactory extends Factory
{
    protected $model = MentorshipRelationship::class;

    public function definition(): array
    {
        return [
            'booking_id'      => null,
            'mentor_id'       => User::factory()->mentor(),
            'freelancer_id'   => User::factory()->freelancer(),
            'status'          => RelationshipStatus::PENDING,
            'payment_type'    => $this->faker->randomElement(['monthly', 'hourly', 'custom']),
            'payment_amount'  => $this->faker->randomFloat(2, 100, 1000),
            'payment_notes'   => $this->faker->sentence(5),
            'requested_at'    => now()->subDay(),
            'duration_months' => $this->faker->numberBetween(1, 12),
            'expires_at'      => now()->addMonths(6),
        ];
    }

    /** Pending relationship */
    public function pending(): static
    {
        return $this->state(fn () => ['status' => RelationshipStatus::PENDING]);
    }

    /** Accepted (active) relationship */
    public function accepted(): static
    {
        return $this->state(fn () => [
            'status'      => RelationshipStatus::ACCEPTED,
            'accepted_at' => now()->subDays(3),
        ]);
    }

    /** Declined relationship */
    public function declined(): static
    {
        return $this->state(fn () => ['status' => RelationshipStatus::DECLINED]);
    }

    /** Ended relationship */
    public function ended(): static
    {
        return $this->state(fn () => [
            'status'   => RelationshipStatus::ENDED,
            'ended_at' => now()->subDay(),
        ]);
    }

    /** Relationship between specific users */
    public function between(User $mentor, User $freelancer): static
    {
        return $this->state(fn () => [
            'mentor_id'     => $mentor->id,
            'freelancer_id' => $freelancer->id,
        ]);
    }
}
