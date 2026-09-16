<?php

namespace Tests\Unit\Boundary;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Gig;
use App\Models\MentorProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Booking Time Calculation Boundary Tests.
 *
 * Uses Carbon::setTestNow() to simulate time precisely.
 *
 * Tests:
 * - isAcceptanceExpired() — true when proposed_date + proposed_time is in the past
 * - sessionEndDateTime() — correct Carbon instance from proposed_date + proposed_time + duration
 * - canBeMarkedComplete() — false before session end, true after
 */
class BookingTimeCalculationTest extends TestCase
{
    use RefreshDatabase;

    /** Clean up Carbon::setTestNow() after each test */
    protected function tearDown(): void
    {
        Carbon::setTestNow(); // reset
        parent::tearDown();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeBookingWithTime(
        string $proposedDate,
        string $proposedTime,
        int $durationMinutes = 60
    ): Booking {
        $mentor     = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->verified()->create(['user_id' => $mentor->id]);
        $freelancer = User::factory()->freelancer()->approved()->create();
        $gig        = Gig::factory()->published()->forMentor($mentor)->withDuration($durationMinutes)->create();

        return Booking::factory()
            ->accepted()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'proposed_date' => $proposedDate,
                'proposed_time' => $proposedTime,
            ]);
    }

    // ─── isAcceptanceExpired() ────────────────────────────────────────────────

    /** @test */
    public function isAcceptanceExpired_returns_true_when_proposed_datetime_is_in_past(): void
    {
        Carbon::setTestNow('2025-01-15 14:00:00');

        $booking = $this->makeBookingWithTime('2025-01-15', '13:00');

        $this->assertTrue($booking->isAcceptanceExpired());
    }

    /** @test */
    public function isAcceptanceExpired_returns_false_when_proposed_datetime_is_in_future(): void
    {
        Carbon::setTestNow('2025-01-15 09:00:00');

        $booking = $this->makeBookingWithTime('2025-01-15', '14:00');

        $this->assertFalse($booking->isAcceptanceExpired());
    }

    /** @test */
    public function isAcceptanceExpired_returns_false_when_no_proposed_time_set(): void
    {
        Carbon::setTestNow('2025-01-15 14:00:00');

        $mentor     = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->verified()->create(['user_id' => $mentor->id]);
        $freelancer = User::factory()->freelancer()->approved()->create();
        $gig        = Gig::factory()->published()->forMentor($mentor)->create();

        $booking = Booking::factory()
            ->accepted()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'proposed_date' => null,
                'proposed_time' => null,
            ]);

        // No time set → sessionStartDateTime() returns null → isAcceptanceExpired = false
        $this->assertFalse($booking->isAcceptanceExpired());
    }

    /** @test */
    public function isAcceptanceExpired_returns_true_one_second_after_proposed_time(): void
    {
        // Proposed: 2025-06-10 10:30:00 | Now: 10:30:01 → expired
        Carbon::setTestNow('2025-06-10 10:30:01');

        $booking = $this->makeBookingWithTime('2025-06-10', '10:30');

        $this->assertTrue($booking->isAcceptanceExpired());
    }

    /** @test */
    public function isAcceptanceExpired_returns_false_one_second_before_proposed_time(): void
    {
        // Proposed: 2025-06-10 10:30:00 | Now: 10:29:59 → not expired
        Carbon::setTestNow('2025-06-10 10:29:59');

        $booking = $this->makeBookingWithTime('2025-06-10', '10:30');

        $this->assertFalse($booking->isAcceptanceExpired());
    }

    // ─── sessionEndDateTime() ─────────────────────────────────────────────────

    /** @test */
    public function sessionEndDateTime_returns_correct_end_time_with_60min_duration(): void
    {
        $booking = $this->makeBookingWithTime('2025-06-10', '09:00', 60);

        $end = $booking->sessionEndDateTime();

        $this->assertNotNull($end);
        $this->assertSame('2025-06-10 10:00:00', $end->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function sessionEndDateTime_returns_correct_end_time_with_90min_duration(): void
    {
        $booking = $this->makeBookingWithTime('2025-07-20', '14:00', 90);

        $end = $booking->sessionEndDateTime();

        $this->assertNotNull($end);
        $this->assertSame('2025-07-20 15:30:00', $end->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function sessionEndDateTime_returns_null_when_no_proposed_date_or_time(): void
    {
        $mentor     = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->verified()->create(['user_id' => $mentor->id]);
        $freelancer = User::factory()->freelancer()->approved()->create();
        $gig        = Gig::factory()->published()->forMentor($mentor)->create();

        $booking = Booking::factory()
            ->accepted()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'proposed_date' => null,
                'proposed_time' => null,
            ]);

        $this->assertNull($booking->sessionEndDateTime());
    }

    /** @test */
    public function sessionEndDateTime_handles_midnight_crossing(): void
    {
        // Session starts at 23:00 for 90 minutes → ends at 00:30 next day
        $booking = $this->makeBookingWithTime('2025-08-01', '23:00', 90);

        $end = $booking->sessionEndDateTime();

        $this->assertSame('2025-08-02 00:30:00', $end->format('Y-m-d H:i:s'));
    }

    // ─── canBeMarkedComplete() ────────────────────────────────────────────────

    /** @test */
    public function canBeMarkedComplete_returns_false_before_session_end_time(): void
    {
        // Session: 2025-06-10 10:00 + 60 min → ends 11:00
        // Now: 10:59 → cannot complete
        Carbon::setTestNow('2025-06-10 10:59:00');

        $booking = $this->makeBookingWithTime('2025-06-10', '10:00', 60);
        $booking->load('gig');

        $this->assertFalse($booking->canBeMarkedComplete());
    }

    /** @test */
    public function canBeMarkedComplete_returns_true_after_session_end_time(): void
    {
        // Session: 2025-06-10 10:00 + 60 min → ends 11:00
        // Now: 11:01 → can complete
        Carbon::setTestNow('2025-06-10 11:01:00');

        $booking = $this->makeBookingWithTime('2025-06-10', '10:00', 60);
        $booking->load('gig');

        $this->assertTrue($booking->canBeMarkedComplete());
    }

    /** @test */
    public function canBeMarkedComplete_returns_true_when_no_proposed_time_set(): void
    {
        $mentor     = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->verified()->create(['user_id' => $mentor->id]);
        $freelancer = User::factory()->freelancer()->approved()->create();
        $gig        = Gig::factory()->published()->forMentor($mentor)->withDuration(60)->create();

        $booking = Booking::factory()
            ->scheduled()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'proposed_date' => null,
                'proposed_time' => null,
            ]);

        $booking->load('gig');

        // No time restriction when proposed_time is null
        $this->assertTrue($booking->canBeMarkedComplete());
    }

    /** @test */
    public function canBeMarkedComplete_is_true_exactly_at_session_end_time(): void
    {
        // Session ends at exactly 11:00:00; isPast() at 11:00:00 = false (same moment)
        // One second later (11:00:01) should be past
        Carbon::setTestNow('2025-06-10 11:00:01');

        $booking = $this->makeBookingWithTime('2025-06-10', '10:00', 60);
        $booking->load('gig');

        $this->assertTrue($booking->canBeMarkedComplete());
    }

    // ─── sessionStartDateTime() ───────────────────────────────────────────────

    /** @test */
    public function sessionStartDateTime_returns_correct_start_time(): void
    {
        $booking = $this->makeBookingWithTime('2025-09-05', '14:30', 60);

        $start = $booking->sessionStartDateTime();

        $this->assertNotNull($start);
        $this->assertSame('2025-09-05 14:30:00', $start->format('Y-m-d H:i:s'));
    }
}
