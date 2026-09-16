<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Models\Gig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'booking_id'  => Booking::factory()->completed(),
            'reviewer_id' => null, // set explicitly in tests
            'reviewee_id' => null, // set explicitly in tests
            'freelancer_id' => null,
            'mentor_id'   => null,
            'gig_id'      => null,
            'rating'      => $this->faker->numberBetween(1, 5),
            'comment'     => $this->faker->paragraph(2),
            'is_public'   => true,
        ];
    }

    /** Review with a specific rating */
    public function withRating(int $rating): static
    {
        return $this->state(fn () => ['rating' => $rating]);
    }

    /** Private review */
    public function private(): static
    {
        return $this->state(fn () => ['is_public' => false]);
    }
}
