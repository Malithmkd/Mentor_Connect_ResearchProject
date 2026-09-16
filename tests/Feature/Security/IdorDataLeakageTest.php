<?php

namespace Tests\Feature\Security;

use App\Models\Booking;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Gig;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\MentorProfile;
use App\Models\MentorshipRelationship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * IDOR (Insecure Direct Object Reference) & Data Leakage Prevention Tests.
 *
 * Validates that:
 * - Freelancer B cannot view/access course modules, lessons, or progress enrolled to Freelancer A
 * - Cross-course traversal is blocked (lesson belonging to different course → 404)
 * - Unenrolled users get 403 on LMS routes
 * - Admin-only data is inaccessible to regular users
 */
class IdorDataLeakageTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeMentor(): User
    {
        $m = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->verified()->create(['user_id' => $m->id]);
        return $m;
    }

    private function makeFreelancer(): User
    {
        return User::factory()->freelancer()->approved()->create();
    }

    private function makeFullLmsSetup(User $mentor, User $freelancer): array
    {
        $gig          = Gig::factory()->published()->forMentor($mentor)->withDuration(60)->create();
        $booking      = Booking::factory()->completed()->between($freelancer, $mentor, $gig)->create();
        $relationship = MentorshipRelationship::factory()
            ->accepted()
            ->between($mentor, $freelancer)
            ->create(['booking_id' => $booking->id]);
        $course       = Course::factory()->published()->forRelationship($relationship)->create();
        $module       = CourseModule::factory()->forCourse($course)->create();
        $lesson       = Lesson::factory()->forModule($module)->create();
        $enrollment   = Enrollment::factory()->forCourseAndFreelancer($course, $freelancer)->create([
            'relationship_id' => $relationship->id,
        ]);

        return compact('gig', 'booking', 'relationship', 'course', 'module', 'lesson', 'enrollment');
    }

    // ─── Enrollment IDOR ──────────────────────────────────────────────────────

    /** @test */
    public function freelancer_b_cannot_access_freelancer_a_enrollment_course_page(): void
    {
        $mentor      = $this->makeMentor();
        $freelancerA = $this->makeFreelancer();
        $freelancerB = $this->makeFreelancer();

        $setup = $this->makeFullLmsSetup($mentor, $freelancerA);

        // FreelancerB tries to access FreelancerA's enrollment
        $this->actingAs($freelancerB)
             ->get(route('lms.course', $setup['enrollment']))
             ->assertForbidden();
    }

    /** @test */
    public function freelancer_b_cannot_access_lesson_inside_freelancer_a_enrollment(): void
    {
        $mentor      = $this->makeMentor();
        $freelancerA = $this->makeFreelancer();
        $freelancerB = $this->makeFreelancer();

        $setup = $this->makeFullLmsSetup($mentor, $freelancerA);

        $this->actingAs($freelancerB)
             ->get(route('lms.lesson', [
                 'enrollment' => $setup['enrollment']->id,
                 'lesson'     => $setup['lesson']->id,
             ]))
             ->assertForbidden();
    }

    /** @test */
    public function freelancer_b_cannot_mark_lesson_complete_in_freelancer_a_enrollment(): void
    {
        $mentor      = $this->makeMentor();
        $freelancerA = $this->makeFreelancer();
        $freelancerB = $this->makeFreelancer();

        $setup = $this->makeFullLmsSetup($mentor, $freelancerA);

        $this->actingAs($freelancerB)
             ->post(route('lms.lesson.complete', [
                 'enrollment' => $setup['enrollment']->id,
                 'lesson'     => $setup['lesson']->id,
             ]))
             ->assertForbidden();
    }

    /** @test */
    public function freelancer_b_cannot_view_progress_page_of_freelancer_a(): void
    {
        $mentor      = $this->makeMentor();
        $freelancerA = $this->makeFreelancer();
        $freelancerB = $this->makeFreelancer();

        $setup = $this->makeFullLmsSetup($mentor, $freelancerA);

        $this->actingAs($freelancerB)
             ->get(route('lms.progress', $setup['enrollment']))
             ->assertForbidden();
    }

    // ─── Cross-Course Lesson Traversal ────────────────────────────────────────

    /** @test */
    public function accessing_lesson_from_different_course_returns_404(): void
    {
        $mentor      = $this->makeMentor();
        $freelancerA = $this->makeFreelancer();

        // Setup for Course 1
        $setupA = $this->makeFullLmsSetup($mentor, $freelancerA);

        // Setup for Course 2 (same freelancer, different course)
        $gig2          = Gig::factory()->published()->forMentor($mentor)->withDuration(60)->create();
        $booking2      = Booking::factory()->completed()->between($freelancerA, $mentor, $gig2)->create();
        $relationship2 = MentorshipRelationship::factory()
            ->accepted()
            ->between($mentor, $freelancerA)
            ->create(['booking_id' => $booking2->id]);
        $course2       = Course::factory()->published()->forRelationship($relationship2)->create();
        $module2       = CourseModule::factory()->forCourse($course2)->create();
        $lessonCourse2 = Lesson::factory()->forModule($module2)->create();
        $enrollment2   = Enrollment::factory()->forCourseAndFreelancer($course2, $freelancerA)->create([
            'relationship_id' => $relationship2->id,
        ]);

        // Use enrollment1 but lesson from course2 → cross-course traversal → should 404
        $this->actingAs($freelancerA)
             ->get(route('lms.lesson', [
                 'enrollment' => $setupA['enrollment']->id,  // enrollment is for course 1
                 'lesson'     => $lessonCourse2->id,          // lesson belongs to course 2
             ]))
             ->assertNotFound();
    }

    // ─── Booking IDOR ─────────────────────────────────────────────────────────

    /** @test */
    public function freelancer_cannot_view_another_freelancers_booking_details(): void
    {
        $mentor      = $this->makeMentor();
        $freelancerA = $this->makeFreelancer();
        $freelancerB = $this->makeFreelancer();
        $gig         = Gig::factory()->published()->forMentor($mentor)->create();

        $booking = Booking::factory()
            ->requested()
            ->between($freelancerA, $mentor, $gig)
            ->create([
                'proposed_date' => now()->addDays(3)->toDateString(),
                'proposed_time' => '10:00',
            ]);

        $this->actingAs($freelancerB)
             ->get(route('freelancer.bookings.show', $booking))
             ->assertForbidden();
    }

    /** @test */
    public function freelancer_cannot_submit_review_for_another_freelancers_booking(): void
    {
        $mentor      = $this->makeMentor();
        $freelancerA = $this->makeFreelancer();
        $freelancerB = $this->makeFreelancer();
        $gig         = Gig::factory()->published()->forMentor($mentor)->create();

        $booking = Booking::factory()
            ->completed()
            ->between($freelancerA, $mentor, $gig)
            ->create();

        $this->actingAs($freelancerB)
             ->post(route('bookings.review', $booking), [
                 'rating'  => 5,
                 'comment' => 'IDOR attempt',
             ])
             ->assertForbidden();
    }

    // ─── Admin Data Protection ────────────────────────────────────────────────

    /** @test */
    public function regular_user_cannot_access_audit_log(): void
    {
        $freelancer = $this->makeFreelancer();

        $this->actingAs($freelancer)
             ->get(route('admin.audit-log'))
             ->assertForbidden();
    }

    /** @test */
    public function regular_user_cannot_access_admin_approval_queue(): void
    {
        $freelancer = $this->makeFreelancer();

        $this->actingAs($freelancer)
             ->get(route('admin.approvals.index'))
             ->assertForbidden();
    }

    /** @test */
    public function regular_user_cannot_access_other_users_admin_profile(): void
    {
        $freelancer = $this->makeFreelancer();
        $otherUser  = $this->makeFreelancer();

        $this->actingAs($freelancer)
             ->get(route('admin.users.show', $otherUser))
             ->assertForbidden();
    }

    // ─── Mentor Cannot Access Student Data ───────────────────────────────────

    /** @test */
    public function one_mentor_cannot_access_another_mentors_course(): void
    {
        $mentor1 = $this->makeMentor();
        $mentor2 = $this->makeMentor();
        $freelancer = $this->makeFreelancer();

        $setup = $this->makeFullLmsSetup($mentor1, $freelancer);

        // mentor2 should not be able to see mentor1's course details
        $this->actingAs($mentor2)
             ->get(route('mentor.lms.courses.show', $setup['course']))
             ->assertForbidden();
    }
}
