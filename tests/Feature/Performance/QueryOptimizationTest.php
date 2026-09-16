<?php

namespace Tests\Feature\Performance;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Gig;
use App\Models\MentorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Query Optimization & Index Efficiency Tests.
 *
 * Validates that composite indexes are used effectively:
 * - (freelancer_id, status) on bookings
 * - (mentor_id, status) on bookings
 *
 * Also validates that scoped queries do not perform full-table scans
 * by verifying query results are correct and complete under large datasets.
 */
class QueryOptimizationTest extends TestCase
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

    // ─── Composite Index: (freelancer_id, status) ─────────────────────────────

    /** @test */
    public function bookings_by_freelancer_and_status_returns_correct_results(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = Gig::factory()->published()->forMentor($mentor)->create();

        // Create 10 bookings in REQUESTED status for this freelancer
        Booking::factory()->count(10)->requested()->between($freelancer, $mentor, $gig)->create([
            'proposed_date' => now()->addDays(3)->toDateString(),
            'proposed_time' => '10:00',
        ]);

        // Create 5 bookings in COMPLETED status for this freelancer
        Booking::factory()->count(5)->completed()->between($freelancer, $mentor, $gig)->create();

        // Create 8 bookings for a DIFFERENT freelancer (noise)
        $otherFreelancer = $this->makeFreelancer();
        Booking::factory()->count(8)->requested()->between($otherFreelancer, $mentor, $gig)->create([
            'proposed_date' => now()->addDays(3)->toDateString(),
            'proposed_time' => '10:00',
        ]);

        // Query by (freelancer_id, status)
        $result = Booking::where('freelancer_id', $freelancer->id)
                         ->where('status', BookingStatus::REQUESTED)
                         ->get();

        // Should return exactly 10 (not contaminated by other freelancer's bookings)
        $this->assertCount(10, $result);
        $result->each(fn ($b) => $this->assertSame($freelancer->id, $b->freelancer_id));
        $result->each(fn ($b) => $this->assertSame(BookingStatus::REQUESTED, $b->status));
    }

    /** @test */
    public function bookings_by_freelancer_scope_returns_only_that_freelancers_bookings(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = Gig::factory()->published()->forMentor($mentor)->create();

        Booking::factory()->count(7)->completed()->between($freelancer, $mentor, $gig)->create();
        // Noise: other freelancers
        Booking::factory()->count(15)->completed()->create();

        $result = Booking::byFreelancer($freelancer->id)->completed()->get();

        $this->assertCount(7, $result);
        $result->each(fn ($b) => $this->assertSame($freelancer->id, $b->freelancer_id));
    }

    // ─── Composite Index: (mentor_id, status) ─────────────────────────────────

    /** @test */
    public function bookings_by_mentor_and_status_returns_correct_results(): void
    {
        $mentor     = $this->makeMentor();
        $gig        = Gig::factory()->published()->forMentor($mentor)->create();
        $otherMentor = $this->makeMentor();
        $otherGig    = Gig::factory()->published()->forMentor($otherMentor)->create();

        // 12 REQUESTED bookings for this mentor
        Booking::factory()->count(12)->requested()->create([
            'mentor_id'     => $mentor->id,
            'gig_id'        => $gig->id,
            'freelancer_id' => fn () => $this->makeFreelancer()->id,
            'proposed_date' => now()->addDays(3)->toDateString(),
            'proposed_time' => '10:00',
        ]);

        // 6 COMPLETED bookings for other mentor (noise)
        Booking::factory()->count(6)->completed()->create([
            'mentor_id'     => $otherMentor->id,
            'gig_id'        => $otherGig->id,
            'freelancer_id' => fn () => $this->makeFreelancer()->id,
        ]);

        $result = Booking::byMentor($mentor->id)
                         ->pendingResponse()
                         ->get();

        $this->assertCount(12, $result);
        $result->each(fn ($b) => $this->assertSame($mentor->id, $b->mentor_id));
        $result->each(fn ($b) => $this->assertSame(BookingStatus::REQUESTED, $b->status));
    }

    /** @test */
    public function bookings_by_mentor_scope_isolates_correctly(): void
    {
        $mentor  = $this->makeMentor();
        $mentor2 = $this->makeMentor();
        $gig1    = Gig::factory()->published()->forMentor($mentor)->create();
        $gig2    = Gig::factory()->published()->forMentor($mentor2)->create();

        Booking::factory()->count(9)->accepted()->create([
            'mentor_id'     => $mentor->id,
            'gig_id'        => $gig1->id,
            'freelancer_id' => fn () => $this->makeFreelancer()->id,
            'proposed_date' => now()->addDays(5)->toDateString(),
            'proposed_time' => '10:00',
        ]);

        Booking::factory()->count(5)->accepted()->create([
            'mentor_id'     => $mentor2->id,
            'gig_id'        => $gig2->id,
            'freelancer_id' => fn () => $this->makeFreelancer()->id,
            'proposed_date' => now()->addDays(5)->toDateString(),
            'proposed_time' => '10:00',
        ]);

        $result = Booking::byMentor($mentor->id)->get();

        $this->assertCount(9, $result);
        $result->each(fn ($b) => $this->assertSame($mentor->id, $b->mentor_id));
    }

    // ─── EXPLAIN Analysis (Index Usage) ──────────────────────────────────────

    /** @test */
    public function booking_query_by_freelancer_and_status_uses_index(): void
    {
        // Skip for SQLite (EXPLAIN syntax differs)
        if (DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped('EXPLAIN index usage test requires MySQL/MariaDB');
        }

        $freelancer = $this->makeFreelancer();

        $explain = DB::select("EXPLAIN SELECT * FROM bookings WHERE freelancer_id = ? AND status = ?", [
            $freelancer->id,
            BookingStatus::REQUESTED->value,
        ]);

        $explainRow = $explain[0];

        // MySQL EXPLAIN should show a key (not null) meaning index is used
        $this->assertNotNull(
            $explainRow->key ?? null,
            'Expected an index to be used for (freelancer_id, status) query on bookings. Possible full table scan.'
        );
    }

    /** @test */
    public function booking_query_by_mentor_and_status_uses_index(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped('EXPLAIN index usage test requires MySQL/MariaDB');
        }

        $mentor = $this->makeMentor();

        $explain = DB::select("EXPLAIN SELECT * FROM bookings WHERE mentor_id = ? AND status = ?", [
            $mentor->id,
            BookingStatus::REQUESTED->value,
        ]);

        $explainRow = $explain[0];

        $this->assertNotNull(
            $explainRow->key ?? null,
            'Expected an index to be used for (mentor_id, status) query on bookings.'
        );
    }

    // ─── Pagination Performance ───────────────────────────────────────────────

    /** @test */
    public function paginated_booking_list_executes_at_most_3_queries(): void
    {
        $mentor = $this->makeMentor();
        $gig    = Gig::factory()->published()->forMentor($mentor)->create();

        Booking::factory()->count(50)->requested()->create([
            'mentor_id'     => $mentor->id,
            'gig_id'        => $gig->id,
            'freelancer_id' => fn () => $this->makeFreelancer()->id,
            'proposed_date' => now()->addDays(3)->toDateString(),
            'proposed_time' => '10:00',
        ]);

        DB::enableQueryLog();
        DB::flushQueryLog();

        // Simulate what mentorIndex() does: paginate with eager loads
        $bookings = Booking::byMentor($mentor->id)
                           ->with(['freelancer', 'gig'])
                           ->recent()
                           ->paginate(10);

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 1 count query + 1 data query + possibly 1 eager-load per relation = ≤ 3
        $this->assertLessThanOrEqual(3, $queryCount,
            "Paginated booking list used {$queryCount} queries — expected ≤ 3 with proper eager loading.");
    }
}
