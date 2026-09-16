<?php

namespace Database\Factories;

use App\Models\CourseModule;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        return [
            'module_id'  => CourseModule::factory(),
            'title'      => $this->faker->sentence(4),
            'content'    => $this->faker->paragraphs(3, true),
            'video_url'  => null,
            'pdf_path'   => null,
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }

    /** Lesson with a YouTube video */
    public function withVideo(): static
    {
        return $this->state(fn () => [
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);
    }

    /** Lesson for a specific module */
    public function forModule(CourseModule $module, int $sortOrder = 1): static
    {
        return $this->state(fn () => [
            'module_id'  => $module->id,
            'sort_order' => $sortOrder,
        ]);
    }
}
