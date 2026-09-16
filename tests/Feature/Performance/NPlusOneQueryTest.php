<?php

namespace Tests\Feature\Performance;

use App\Models\Booking;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Gig;
use App\Models\Lesson;
use App\Models\MentorProfile;
use App\Models\MentorshipRelationship;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * N+1 Query Detection Tests.
 *
 * Uses DB::listen() to count the number of queries executed during
 * heavy route rendering and asserts they stay below defined thresholds.
 *
 * Routes tested:
 * - GET /mentors (gig listing with skills + mentor profiles)
 * - GET /mentor/bookings (mentor booking list with gig + freelancer eager loads)
 * - GET lms/ (LMS index with courses + modules + lessons)
 */
class NPlusOneQueryTest extends TestCase
{
    use RefreshDatabase;

    /** Maximum allowed queries for each route. */
    private const MAX_QUERIES = [
        'gigs_listing'     => 10,  // pagination + eager loads
        'mentor_bookings'  => 8,
        'lms_index'        => 8,
    ];

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

    private function listenQueryCount(\Closure $callback): int
    {
        $count = 0;
        $listener = DB::listen(function () use (&$count) {
            $count++;
        });

        $callback();

        // Remove listener
        DB::flushQueryLog();

        return $count;
    }

    // ─── Gig Listing: /mentors ────────────────────────────────────────────────

    /** @test */
    public function gig_listing_page_does_not_trigger_n_plus_one_queries(): void
    {
        $mentor = $this->makeMentor();

        // Create 10 published gigs each with 3 skills
        $skills = Skill::factory()->count(5)->create();

        Gig::factory()->count(10)->published()->forMentor($mentor)->create()
            ->each(fn ($gig) => $gig->skills()->attach(
                $skills->random(3)->pluck('id')
            ));

        $freelancer = $this->makeFreelancer();

        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->actingAs($freelancer)->get(route('gigs.index'));

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            self::MAX_QUERIES['gigs_listing'],
            $queryCount,
            "Gig listing executed {$queryCount} queries, expected ≤ " . self::MAX_QUERIES['gigs_listing'] . '. Possible N+1 detected.'
        );
    }

    /** @test */
    public function gig_listing_query_count_does_not_grow_with_more_gigs(): void
    {
        $mentor = $this->makeMentor();
        $skills = Skill::factory()->count(3)->create();

        // 5 gigs
        Gig::factory()->count(5)->published()->forMentor($mentor)->create()
            ->each(fn ($gig) => $gig->skills()->attach($skills->pluck('id')));

        $freelancer = $this->makeFreelancer();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->actingAs($freelancer)->get(route('gigs.index'));
        $count5 = count(DB::getQueryLog());

        DB::flushQueryLog();

        // 20 more gigs
        Gig::factory()->count(20)->published()->forMentor($mentor)->create()
            ->each(fn ($gig) => $gig->skills()->attach($skills->pluck('id')));

        $this->actingAs($freelancer)->get(route('gigs.index'));
        $count25 = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Query count should not scale linearly with gig count (N+1 would do this)
        // Allow tolerance of ±3 queries for pagination metadata etc.
        $this->assertLessThanOrEqual(
            $count5 + 3,
            $count25,
            'Query count grew suspiciously when adding more gigs — potential N+1 detected.'
        );
    }

    // ─── Mentor Booking List ──────────────────────────────────────────────────

    /** @test */
    public function mentor_booking_list_does_not_trigger_n_plus_one_queries(): void
    {
        $mentor = $this->makeMentor();

        // Create 10 bookings for this mentor
        Booking::factory()->count(10)->create([
            'mentor_id'     => $mentor->id,
            'freelancer_id' => fn () => User::factory()->freelancer()->approved()->create()->id,
            'gig_id'        => fn () => Gig::factory()->published()->forMentor($mentor)->create()->id,
        ]);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->actingAs($mentor)->get(route('mentor.bookings.index'));

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            self::MAX_QUERIES['mentor_bookings'],
            $queryCount,
            "Mentor bookings list executed {$queryCount} queries, expected ≤ " . self::MAX_QUERIES['mentor_bookings']
        );
    }

    // ─── LMS Index ────────────────────────────────────────────────────────────

    /** @test */
    public function lms_index_does_not_trigger_n_plus_one_queries(): void
    {
        $mentor     = $this->makeMentor();
        $freelancer = $this->makeFreelancer();

        // Create 3 mentorship relationships → 3 courses → modules → lessons
        $gig = Gig::factory()->published()->forMentor($mentor)->create();

        for ($i = 0; $i < 3; $i++) {
            $booking = Booking::factory()
                ->completed()
                ->between($freelancer, $mentor, $gig)
                ->create();

            $relationship = MentorshipRelationship::factory()
                ->accepted()
                ->between($mentor, $freelancer)
                ->create(['booking_id' => $booking->id]);

            $course  = Course::factory()->published()->forRelationship($relationship)->create();
            $module  = CourseModule::factory()->forCourse($course)->create();
            Lesson::factory()->forModule($module)->count(3)->create();

            Enrollment::factory()->forCourseAndFreelancer($course, $freelancer)->create([
                'relationship_id' => $relationship->id,
            ]);
        }

        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->actingAs($freelancer)->get(route('lms.index'));

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            self::MAX_QUERIES['lms_index'],
            $queryCount,
            "LMS index executed {$queryCount} queries, expected ≤ " . self::MAX_QUERIES['lms_index']
        );
    }

    // ─── Gig Show Page ────────────────────────────────────────────────────────

    /** @test */
    public function gig_show_page_queries_are_bounded(): void
    {
        $mentor     = $this->makeMentor();
        $freelancer = $this->makeFreelancer();

        $skills = Skill::factory()->count(5)->create();
        $gig    = Gig::factory()->published()->forMentor($mentor)->create();
        $gig->skills()->attach($skills->pluck('id'));

        // Add some reviews
        Booking::factory()
            ->count(3)
            ->completed()
            ->create([
                'mentor_id'     => $mentor->id,
                'freelancer_id' => fn () => User::factory()->freelancer()->approved()->create()->id,
                'gig_id'        => $gig->id,
            ]);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->actingAs($freelancer)->get(route('gigs.show', $gig->slug));

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Single gig show should not need more than 10 queries
        $this->assertLessThanOrEqual(10, $queryCount,
            "Gig show page executed {$queryCount} queries — possible N+1 detected.");
    }
}
