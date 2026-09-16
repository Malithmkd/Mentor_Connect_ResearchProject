<?php

namespace Tests\Feature\NonFunctional;

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Gig;
use App\Models\Lesson;
use App\Models\MentorProfile;
use App\Models\MentorshipRelationship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Database Referential Integrity Tests.
 *
 * Validates:
 * - FK cascade delete: deleting course_module deletes its lessons
 * - Soft-delete on gigs (deleted_at set, record not gone)
 * - Unique constraints (email uniqueness, review uniqueness)
 * - Restore from soft-delete
 */
class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    // ─── FK Cascade: CourseModule → Lessons ──────────────────────────────────

    /** @test */
    public function deleting_course_module_cascades_to_its_lessons(): void
    {
        $mentor       = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->verified()->create(['user_id' => $mentor->id]);
        $relationship = MentorshipRelationship::factory()->accepted()->between($mentor, User::factory()->freelancer()->create())->create();

        $course  = Course::factory()->forRelationship($relationship)->create();
        $module  = CourseModule::factory()->forCourse($course)->create();
        $lesson1 = Lesson::factory()->forModule($module, 1)->create();
        $lesson2 = Lesson::factory()->forModule($module, 2)->create();

        $this->assertDatabaseHas('lessons', ['id' => $lesson1->id]);
        $this->assertDatabaseHas('lessons', ['id' => $lesson2->id]);

        // Delete the module — should cascade to lessons
        $module->delete();

        $this->assertDatabaseMissing('course_modules', ['id' => $module->id]);
        $this->assertDatabaseMissing('lessons', ['id' => $lesson1->id]);
        $this->assertDatabaseMissing('lessons', ['id' => $lesson2->id]);
    }

    /** @test */
    public function deleting_course_cascades_to_course_modules_and_lessons(): void
    {
        $mentor       = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->verified()->create(['user_id' => $mentor->id]);
        $relationship = MentorshipRelationship::factory()->accepted()->between($mentor, User::factory()->freelancer()->create())->create();

        $course  = Course::factory()->forRelationship($relationship)->create();
        $module  = CourseModule::factory()->forCourse($course)->create();
        $lesson  = Lesson::factory()->forModule($module)->create();

        $course->delete();

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
        $this->assertDatabaseMissing('course_modules', ['id' => $module->id]);
        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    }

    // ─── Soft Deletes: Gigs ───────────────────────────────────────────────────

    /** @test */
    public function soft_deleting_a_gig_sets_deleted_at_without_removing_the_record(): void
    {
        $mentor = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->verified()->create(['user_id' => $mentor->id]);
        $gig    = Gig::factory()->published()->forMentor($mentor)->create();

        $this->actingAs($mentor)
             ->delete(route('mentor.gigs.destroy', $gig))
             ->assertRedirect();

        // Record still exists with deleted_at set
        $this->assertSoftDeleted('gigs', ['id' => $gig->id]);
        $this->assertDatabaseHas('gigs', ['id' => $gig->id]);

        // Not returned by default Eloquent queries
        $this->assertNull(Gig::find($gig->id));
        $this->assertNotNull(Gig::withTrashed()->find($gig->id));
    }

    /** @test */
    public function soft_deleted_gig_can_be_restored(): void
    {
        $mentor = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->verified()->create(['user_id' => $mentor->id]);
        $gig    = Gig::factory()->draft()->forMentor($mentor)->create();

        $gig->delete();
        $this->assertSoftDeleted('gigs', ['id' => $gig->id]);

        $this->actingAs($mentor)
             ->post(route('mentor.gigs.restore', $gig->id))
             ->assertSessionHasNoErrors();

        $this->assertNotSoftDeleted('gigs', ['id' => $gig->id]);
    }

    // ─── Soft Deletes: Users ──────────────────────────────────────────────────

    /** @test */
    public function admin_can_soft_delete_a_user(): void
    {
        $admin      = User::factory()->admin()->create();
        $targetUser = User::factory()->freelancer()->approved()->create();

        $this->actingAs($admin)
             ->delete(route('admin.users.destroy', $targetUser))
             ->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $targetUser->id]);
        $this->assertNull(User::find($targetUser->id));
        $this->assertNotNull(User::withTrashed()->find($targetUser->id));
    }

    // ─── Unique Constraints ───────────────────────────────────────────────────

    /** @test */
    public function duplicate_email_registration_fails_with_validation_error(): void
    {
        $existingUser = User::factory()->freelancer()->create(['email' => 'exists@example.com']);

        $response = $this->post('/register', [
            'first_name'            => 'Dupe',
            'last_name'             => 'User',
            'email'                 => 'exists@example.com',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role'                  => 'freelancer',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function database_enforces_unique_email_at_db_level(): void
    {
        User::factory()->create(['email' => 'unique@test.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('users')->insert([
            'first_name'  => 'Another',
            'last_name'   => 'User',
            'email'       => 'unique@test.com',
            'password'    => bcrypt('password'),
            'role'        => 'freelancer',
            'is_active'   => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    // ─── Review Uniqueness (booking_id + reviewer_id) ─────────────────────────

    /** @test */
    public function database_enforces_unique_review_per_booking_per_reviewer(): void
    {
        $freelancer = User::factory()->freelancer()->approved()->create();
        $mentor     = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->verified()->create(['user_id' => $mentor->id]);
        $gig        = Gig::factory()->published()->forMentor($mentor)->create();
        $booking    = \App\Models\Booking::factory()->completed()->between($freelancer, $mentor, $gig)->create();

        // Insert first review at DB level
        DB::table('reviews')->insert([
            'booking_id'  => $booking->id,
            'reviewer_id' => $freelancer->id,
            'reviewee_id' => $mentor->id,
            'freelancer_id' => $freelancer->id,
            'mentor_id'   => $mentor->id,
            'gig_id'      => $gig->id,
            'rating'      => 5,
            'is_public'   => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Attempt to insert a duplicate should throw
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('reviews')->insert([
            'booking_id'  => $booking->id,
            'reviewer_id' => $freelancer->id,
            'reviewee_id' => $mentor->id,
            'freelancer_id' => $freelancer->id,
            'mentor_id'   => $mentor->id,
            'gig_id'      => $gig->id,
            'rating'      => 1,
            'is_public'   => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }
}
