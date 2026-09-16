<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseModule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseModule>
 */
class CourseModuleFactory extends Factory
{
    protected $model = CourseModule::class;

    public function definition(): array
    {
        return [
            'course_id'   => Course::factory(),
            'title'       => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'sort_order'  => $this->faker->numberBetween(1, 10),
        ];
    }

    /** Module for a specific course */
    public function forCourse(Course $course, int $sortOrder = 1): static
    {
        return $this->state(fn () => [
            'course_id'  => $course->id,
            'sort_order' => $sortOrder,
        ]);
    }
}
