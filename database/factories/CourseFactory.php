<?php

namespace Database\Factories;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\MentorshipRelationship;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'relationship_id' => MentorshipRelationship::factory()->accepted(),
            'mentor_id'       => User::factory()->mentor(),
            'title'           => $this->faker->sentence(4),
            'description'     => $this->faker->paragraph(2),
            'status'          => CourseStatus::DRAFT,
        ];
    }

    /** Draft course */
    public function draft(): static
    {
        return $this->state(fn () => ['status' => CourseStatus::DRAFT]);
    }

    /** Published course */
    public function published(): static
    {
        return $this->state(fn () => ['status' => CourseStatus::PUBLISHED]);
    }

    /** Archived course */
    public function archived(): static
    {
        return $this->state(fn () => ['status' => CourseStatus::ARCHIVED]);
    }

    /** Attach to a specific relationship and mentor */
    public function forRelationship(MentorshipRelationship $relationship): static
    {
        return $this->state(fn () => [
            'relationship_id' => $relationship->id,
            'mentor_id'       => $relationship->mentor_id,
        ]);
    }
}
