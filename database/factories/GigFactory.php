<?php

namespace Database\Factories;

use App\Enums\GigStatus;
use App\Models\Gig;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gig>
 */
class GigFactory extends Factory
{
    protected $model = Gig::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(5);

        return [
            'mentor_id'               => User::factory()->mentor(),
            'title'                   => $title,
            'slug'                    => \Illuminate\Support\Str::slug($title) . '-' . uniqid(),
            'description'             => $this->faker->paragraph(3),
            'what_to_expect'          => $this->faker->paragraph(2),
            'prerequisites'           => $this->faker->sentence(8),
            'delivery_format'         => $this->faker->randomElement(['video_call', 'chat', 'async']),
            'experience_level'        => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'duration_minutes'        => $this->faker->randomElement([30, 45, 60, 90, 120]),
            'price'                   => $this->faker->randomFloat(2, 500, 5000),
            'status'                  => GigStatus::PUBLISHED,
            'max_sessions_per_week'   => $this->faker->numberBetween(1, 10),
            'booking_lead_time_hours' => $this->faker->numberBetween(1, 72),
            'average_rating'          => 0.0,
            'total_bookings'          => 0,
        ];
    }

    /** Published gig */
    public function published(): static
    {
        return $this->state(fn () => ['status' => GigStatus::PUBLISHED]);
    }

    /** Draft gig */
    public function draft(): static
    {
        return $this->state(fn () => ['status' => GigStatus::DRAFT]);
    }

    /** Paused gig */
    public function paused(): static
    {
        return $this->state(fn () => ['status' => GigStatus::PAUSED]);
    }

    /** Archived gig */
    public function archived(): static
    {
        return $this->state(fn () => ['status' => GigStatus::ARCHIVED]);
    }

    /** Gig with specific duration (for time calculation tests) */
    public function withDuration(int $minutes): static
    {
        return $this->state(fn () => ['duration_minutes' => $minutes]);
    }

    /** Assign a specific mentor */
    public function forMentor(User $mentor): static
    {
        return $this->state(fn () => ['mentor_id' => $mentor->id]);
    }
}
