<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\MentorshipRelationship;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'course_id'       => Course::factory()->published(),
            'relationship_id' => MentorshipRelationship::factory()->accepted(),
            'freelancer_id'   => User::factory()->freelancer(),
            'enrolled_at'     => now(),
            'completed_at'    => null,
        ];
    }

    /** Completed enrollment */
    public function completed(): static
    {
        return $this->state(fn () => ['completed_at' => now()->subDay()]);
    }

    /** Enrollment for a specific course and freelancer */
    public function forCourseAndFreelancer(Course $course, User $freelancer): static
    {
        return $this->state(fn () => [
            'course_id'     => $course->id,
            'freelancer_id' => $freelancer->id,
        ]);
    }
}
