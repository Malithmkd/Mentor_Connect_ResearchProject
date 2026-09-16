<?php

namespace Database\Factories;

use App\Models\MentorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MentorProfile>
 */
class MentorProfileFactory extends Factory
{
    protected $model = MentorProfile::class;

    public function definition(): array
    {
        return [
            'user_id'             => User::factory(),
            'headline'            => $this->faker->sentence(6),
            'about'               => $this->faker->paragraph(3),
            'company'             => $this->faker->company(),
            'website'             => $this->faker->url(),
            'linkedin_url'        => 'https://linkedin.com/in/' . $this->faker->userName(),
            'github_url'          => 'https://github.com/' . $this->faker->userName(),
            'years_experience'    => $this->faker->numberBetween(1, 20),
            'hourly_rate'         => $this->faker->randomFloat(2, 20, 500),
            'verification_status' => 'verified',
            'verified_at'         => now()->subDays(30),
            'average_rating'      => 0.0,
            'total_reviews'       => 0,
        ];
    }

    /** Mentor profile in pending verification state */
    public function pending(): static
    {
        return $this->state(fn () => [
            'verification_status' => 'pending',
            'verified_at'         => null,
        ]);
    }

    /** Mentor profile in verified state */
    public function verified(): static
    {
        return $this->state(fn () => [
            'verification_status' => 'verified',
            'verified_at'         => now()->subDays(30),
        ]);
    }
}
