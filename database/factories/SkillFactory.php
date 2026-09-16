<?php

namespace Database\Factories;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Skill>
 */
class SkillFactory extends Factory
{
    protected $model = Skill::class;

    private static int $index = 0;

    private static array $skills = [
        'PHP', 'Laravel', 'Vue.js', 'React', 'Python', 'Machine Learning',
        'Docker', 'Kubernetes', 'AWS', 'System Design', 'Node.js', 'TypeScript',
        'Java', 'Spring Boot', 'DevOps', 'Data Science', 'UI/UX Design', 'Figma',
        'Career Coaching', 'Leadership', 'SQL', 'MongoDB', 'Redis', 'GraphQL',
    ];

    public function definition(): array
    {
        $name = self::$skills[self::$index % count(self::$skills)] . '-' . uniqid();
        self::$index++;

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
        ];
    }
}
