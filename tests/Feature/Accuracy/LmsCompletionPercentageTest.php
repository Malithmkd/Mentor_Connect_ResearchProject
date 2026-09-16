<?php

namespace Tests\Feature\Accuracy;

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\MentorProfile;
use App\Models\MentorshipRelationship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LMS Completion Percentage Accuracy Tests.
 *
 * Validates the getProgressPercentageAttribute() computation on Enrollment:
 * - 0% when no lessons are completed
 * - 50% when half the lessons are done
 * - 100% when all lessons are done
 * - Correct integer rounding for non-whole percentages
 * - Per-module completion tracking
 * - Edge case: course with zero lessons returns 0%
 */
class LmsCompletionPercentageTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeMentor(): User
    {
        $mentor = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->verified()->create(['user_id' => $mentor->id]);
        return $mentor;
    }

    private function makeFreelancer(): User
    {
        return User::factory()->freelancer()->approved()->create();
    }

    private function makeRelationship(User $mentor, User $freelancer): MentorshipRelationship
    {
        return MentorshipRelationship::factory()
            ->accepted()
            ->between($mentor, $freelancer)
            ->create();
    }

    private function makeCourseWithLessons(
        MentorshipRelationship $relationship,
        int $numModules = 1,
        int $lessonsPerModule = 3
    ): array {
        $course  = Course::factory()->published()->forRelationship($relationship)->create();
        $lessons = [];

        for ($m = 1; $m <= $numModules; $m++) {
            $module = CourseModule::factory()->forCourse($course, $m)->create();
            for ($l = 1; $l <= $lessonsPerModule; $l++) {
                $lessons[] = Lesson::factory()->forModule($module, $l)->create();
            }
        }

        return [$course, $lessons];
    }

    private function makeEnrollment(
        Course $course,
        User $freelancer,
        MentorshipRelationship $relationship
    ): Enrollment {
        return Enrollment::factory()
            ->forCourseAndFreelancer($course, $freelancer)
            ->create(['relationship_id' => $relationship->id]);
    }

    private function completeLesson(Enrollment $enrollment, Lesson $lesson, User $freelancer): void
    {
        LessonProgress::firstOrCreate(
            [
                'enrollment_id' => $enrollment->id,
                'lesson_id'     => $lesson->id,
                'freelancer_id' => $freelancer->id,
            ],
            ['completed_at' => now()]
        );
    }

    // ─── Zero Lessons ─────────────────────────────────────────────────────────

    /** @test */
    public function completion_percentage_is_zero_when_course_has_no_lessons(): void
    {
        $mentor     = $this->makeMentor();
        $freelancer = $this->makeFreelancer();
        $rel        = $this->makeRelationship($mentor, $freelancer);

        $course     = Course::factory()->published()->forRelationship($rel)->create();
        $enrollment = $this->makeEnrollment($course, $freelancer, $rel);

        $this->assertSame(0, $enrollment->progress_percentage);
    }

    // ─── No Completions ───────────────────────────────────────────────────────

    /** @test */
    public function completion_percentage_is_zero_when_no_lessons_completed(): void
    {
        $mentor     = $this->makeMentor();
        $freelancer = $this->makeFreelancer();
        $rel        = $this->makeRelationship($mentor, $freelancer);

        [$course, $lessons] = $this->makeCourseWithLessons($rel, 1, 4);
        $enrollment         = $this->makeEnrollment($course, $freelancer, $rel);

        $this->assertSame(0, $enrollment->progress_percentage);
    }

    // ─── Partial Completions ──────────────────────────────────────────────────

    /** @test */
    public function completion_percentage_is_50_when_half_lessons_are_done(): void
    {
        $mentor     = $this->makeMentor();
        $freelancer = $this->makeFreelancer();
        $rel        = $this->makeRelationship($mentor, $freelancer);

        [$course, $lessons] = $this->makeCourseWithLessons($rel, 1, 4);
        $enrollment         = $this->makeEnrollment($course, $freelancer, $rel);

        // Complete 2 out of 4 lessons
        $this->completeLesson($enrollment, $lessons[0], $freelancer);
        $this->completeLesson($enrollment, $lessons[1], $freelancer);

        // Force fresh to clear cached counts
        $this->assertSame(50, $enrollment->fresh()->progress_percentage);
    }

    /** @test */
    public function completion_percentage_is_25_when_one_of_four_lessons_done(): void
    {
        $mentor     = $this->makeMentor();
        $freelancer = $this->makeFreelancer();
        $rel        = $this->makeRelationship($mentor, $freelancer);

        [$course, $lessons] = $this->makeCourseWithLessons($rel, 1, 4);
        $enrollment         = $this->makeEnrollment($course, $freelancer, $rel);

        $this->completeLesson($enrollment, $lessons[0], $freelancer);

        $this->assertSame(25, $enrollment->fresh()->progress_percentage);
    }

    /** @test */
    public function completion_percentage_rounds_correctly_for_non_whole_values(): void
    {
        $mentor     = $this->makeMentor();
        $freelancer = $this->makeFreelancer();
        $rel        = $this->makeRelationship($mentor, $freelancer);

        // 3 lessons — completing 1 = 33.33% → rounds to 33
        [$course, $lessons] = $this->makeCourseWithLessons($rel, 1, 3);
        $enrollment         = $this->makeEnrollment($course, $freelancer, $rel);

        $this->completeLesson($enrollment, $lessons[0], $freelancer);

        $this->assertSame(33, $enrollment->fresh()->progress_percentage);
    }

    /** @test */
    public function completion_percentage_rounds_67_for_two_of_three_lessons(): void
    {
        $mentor     = $this->makeMentor();
        $freelancer = $this->makeFreelancer();
        $rel        = $this->makeRelationship($mentor, $freelancer);

        [$course, $lessons] = $this->makeCourseWithLessons($rel, 1, 3);
        $enrollment         = $this->makeEnrollment($course, $freelancer, $rel);

        $this->completeLesson($enrollment, $lessons[0], $freelancer);
        $this->completeLesson($enrollment, $lessons[1], $freelancer);

        // 2/3 = 66.67% → rounds to 67
        $this->assertSame(67, $enrollment->fresh()->progress_percentage);
    }

    // ─── Full Completion ──────────────────────────────────────────────────────

    /** @test */
    public function completion_percentage_is_100_when_all_lessons_are_done(): void
    {
        $mentor     = $this->makeMentor();
        $freelancer = $this->makeFreelancer();
        $rel        = $this->makeRelationship($mentor, $freelancer);

        [$course, $lessons] = $this->makeCourseWithLessons($rel, 2, 3); // 6 lessons total
        $enrollment         = $this->makeEnrollment($course, $freelancer, $rel);

        foreach ($lessons as $lesson) {
            $this->completeLesson($enrollment, $lesson, $freelancer);
        }

        $this->assertSame(100, $enrollment->fresh()->progress_percentage);
    }

    // ─── Multi-Module Percentage ──────────────────────────────────────────────

    /** @test */
    public function completion_percentage_spans_all_modules_correctly(): void
    {
        $mentor     = $this->makeMentor();
        $freelancer = $this->makeFreelancer();
        $rel        = $this->makeRelationship($mentor, $freelancer);

        // 2 modules × 3 lessons each = 6 lessons total
        [$course, $lessons] = $this->makeCourseWithLessons($rel, 2, 3);
        $enrollment         = $this->makeEnrollment($course, $freelancer, $rel);

        // Complete 3 of 6 lessons (from module 1)
        $this->completeLesson($enrollment, $lessons[0], $freelancer);
        $this->completeLesson($enrollment, $lessons[1], $freelancer);
        $this->completeLesson($enrollment, $lessons[2], $freelancer);

        $this->assertSame(50, $enrollment->fresh()->progress_percentage);
    }

    // ─── Isolation: Other Freelancer's Progress ───────────────────────────────

    /** @test */
    public function completion_percentage_does_not_count_another_freelancers_progress(): void
    {
        $mentor      = $this->makeMentor();
        $freelancer1 = $this->makeFreelancer();
        $freelancer2 = $this->makeFreelancer();
        $rel         = $this->makeRelationship($mentor, $freelancer1);

        [$course, $lessons] = $this->makeCourseWithLessons($rel, 1, 4);

        // Enroll both freelancers in the same course
        $enrollment1 = $this->makeEnrollment($course, $freelancer1, $rel);
        $enrollment2 = $this->makeEnrollment($course, $freelancer2, $rel);

        // Only freelancer2 completes all lessons
        foreach ($lessons as $lesson) {
            $this->completeLesson($enrollment2, $lesson, $freelancer2);
        }

        // freelancer1's progress should still be 0%
        $this->assertSame(0, $enrollment1->fresh()->progress_percentage);
        // freelancer2's progress should be 100%
        $this->assertSame(100, $enrollment2->fresh()->progress_percentage);
    }

    // ─── isCompletedBy Helper ─────────────────────────────────────────────────

    /** @test */
    public function lesson_is_completed_by_returns_true_only_for_the_completing_freelancer(): void
    {
        $mentor      = $this->makeMentor();
        $freelancer1 = $this->makeFreelancer();
        $freelancer2 = $this->makeFreelancer();
        $rel         = $this->makeRelationship($mentor, $freelancer1);

        [$course, $lessons] = $this->makeCourseWithLessons($rel, 1, 1);
        $lesson             = $lessons[0];
        $enrollment         = $this->makeEnrollment($course, $freelancer1, $rel);

        $this->completeLesson($enrollment, $lesson, $freelancer1);

        $this->assertTrue($lesson->isCompletedBy($freelancer1->id));
        $this->assertFalse($lesson->isCompletedBy($freelancer2->id));
    }
}
