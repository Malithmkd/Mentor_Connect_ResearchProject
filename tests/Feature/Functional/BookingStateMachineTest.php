<?php

namespace Tests\Feature\Functional;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Gig;
use App\Models\MentorProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Booking State Machine Feature Tests.
 *
 * Validates the full lifecycle:
 * draft -> requested -> accepted -> scheduled -> completed -> reviewed
 *
 * And negative flows:
 * - Cannot skip states
 * - Cannot book own gig
 * - Acceptance blocked when proposed date is past (isAcceptanceExpired)
 * - Completion blocked before session end time (canBeMarkedComplete)
 */
class BookingStateMachineTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeFreelancer(): User
    {
        return User::factory()->freelancer()->approved()->create();
    }

    private function makeMentor(): User
    {
        $mentor = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->verified()->create(['user_id' => $mentor->id]);
        return $mentor;
    }

    private function makePublishedGig(User $mentor): Gig
    {
        return Gig::factory()->published()->forMentor($mentor)->withDuration(60)->create();
    }

    // ─── Booking Creation ─────────────────────────────────────────────────────

    /** @test */
    public function freelancer_can_request_a_booking_on_published_gig(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makePublishedGig($mentor);

        $response = $this->actingAs($freelancer)
                         ->post(route('bookings.store'), [
                             'gig_id'        => $gig->id,
                             'proposed_date' => now()->addDays(7)->toDateString(),
                             'proposed_time' => '10:00',
                             'freelancer_note' => 'Looking forward to the session!',
                         ]);

        $response->assertRedirect(route('freelancer.bookings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'freelancer_id' => $freelancer->id,
            'gig_id'        => $gig->id,
            'status'        => BookingStatus::REQUESTED->value,
        ]);
    }

    /** @test */
    public function mentor_cannot_book_their_own_gig(): void
    {
        $mentor = $this->makeMentor();
        $gig    = $this->makePublishedGig($mentor);

        // Act as the mentor trying to book their own gig
        $response = $this->actingAs($mentor)
                         ->post(route('bookings.store'), [
                             'gig_id'        => $gig->id,
                             'proposed_date' => now()->addDays(3)->toDateString(),
                             'proposed_time' => '14:00',
                         ]);

        // FormRequest authorize() blocks non-freelancers with 403 Forbidden
        $response->assertForbidden();
        $this->assertDatabaseMissing('bookings', ['gig_id' => $gig->id]);
    }

    /** @test */
    public function freelancer_cannot_book_a_draft_gig(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = Gig::factory()->draft()->forMentor($mentor)->create();

        $this->actingAs($freelancer)
             ->post(route('bookings.store'), [
                 'gig_id'        => $gig->id,
                 'proposed_date' => now()->addDays(3)->toDateString(),
                 'proposed_time' => '10:00',
             ])
             ->assertStatus(404); // findOrFail on published() scope fails
    }

    // ─── State Transitions ────────────────────────────────────────────────────

    /** @test */
    public function mentor_can_accept_a_requested_booking(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makePublishedGig($mentor);

        $booking = Booking::factory()
            ->requested()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'proposed_date' => now()->addDays(7)->toDateString(),
                'proposed_time' => '10:00',
            ]);

        $this->actingAs($mentor)
             ->patch(route('bookings.status', $booking), [
                 'status' => BookingStatus::ACCEPTED->value,
             ])
             ->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'id'     => $booking->id,
            'status' => BookingStatus::ACCEPTED->value,
        ]);
    }

    /** @test */
    public function mentor_can_reject_a_requested_booking(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makePublishedGig($mentor);

        $booking = Booking::factory()
            ->requested()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'proposed_date' => now()->addDays(7)->toDateString(),
                'proposed_time' => '10:00',
            ]);

        $this->actingAs($mentor)
             ->patch(route('bookings.status', $booking), [
                 'status' => BookingStatus::REJECTED->value,
                 'note'   => 'Unavailable on that date.',
             ])
             ->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'id'          => $booking->id,
            'status'      => BookingStatus::REJECTED->value,
            'mentor_note' => 'Unavailable on that date.',
        ]);
    }

    /** @test */
    public function mentor_can_schedule_an_accepted_booking(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makePublishedGig($mentor);

        $booking = Booking::factory()
            ->accepted()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'proposed_date' => now()->addDays(3)->toDateString(),
                'proposed_time' => '14:00',
            ]);

        $this->actingAs($mentor)
             ->patch(route('bookings.status', $booking), [
                 'status'       => BookingStatus::SCHEDULED->value,
                 'meeting_link' => 'https://meet.google.com/abc-def-ghi',
             ])
             ->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'id'     => $booking->id,
            'status' => BookingStatus::SCHEDULED->value,
        ]);
    }

    /** @test */
    public function mentor_can_complete_a_scheduled_booking_after_session_ends(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makePublishedGig($mentor); // 60 min duration

        // Session was yesterday at 10:00 — end time (11:00) has definitely passed
        $booking = Booking::factory()
            ->scheduled()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'proposed_date' => now()->subDay()->toDateString(),
                'proposed_time' => '10:00',
            ]);

        $this->actingAs($mentor)
             ->patch(route('bookings.status', $booking), [
                 'status' => BookingStatus::COMPLETED->value,
             ])
             ->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'id'     => $booking->id,
            'status' => BookingStatus::COMPLETED->value,
        ]);
    }

    /** @test */
    public function mentor_cannot_complete_booking_before_session_end_time(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makePublishedGig($mentor); // 60 min duration

        // Session starts 2 hours in the future — end time has not passed
        $booking = Booking::factory()
            ->scheduled()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'proposed_date' => now()->addDay()->toDateString(),
                'proposed_time' => now()->addHours(2)->format('H:i'),
            ]);

        // The policy `complete()` calls canBeMarkedComplete() → false → 403
        $this->actingAs($mentor)
             ->patch(route('bookings.status', $booking), [
                 'status' => BookingStatus::COMPLETED->value,
             ])
             ->assertForbidden();
    }

    /** @test */
    public function freelancer_can_submit_review_for_completed_booking(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makePublishedGig($mentor);

        $booking = Booking::factory()
            ->completed()
            ->between($freelancer, $mentor, $gig)
            ->create();

        $this->actingAs($freelancer)
             ->post(route('bookings.review', $booking), [
                 'booking_id' => $booking->id,
                 'rating'    => 5,
                 'comment'   => 'Excellent session!',
                 'is_public' => true,
             ])
             ->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'booking_id'  => $booking->id,
            'reviewer_id' => $freelancer->id,
            'rating'      => 5,
        ]);

        // Booking transitions to reviewed
        $this->assertDatabaseHas('bookings', [
            'id'     => $booking->id,
            'status' => BookingStatus::REVIEWED->value,
        ]);
    }

    // ─── Acceptance Expiry Guard ──────────────────────────────────────────────

    /** @test */
    public function mentor_cannot_accept_booking_with_expired_proposed_date(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makePublishedGig($mentor);

        // Proposed time is in the past → isAcceptanceExpired() == true
        $booking = Booking::factory()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'status'        => BookingStatus::REQUESTED,
                'proposed_date' => now()->subDays(2)->toDateString(),
                'proposed_time' => '08:00',
                'requested_at'  => now()->subDays(3),
            ]);

        $this->actingAs($mentor)
             ->patch(route('bookings.status', $booking), [
                 'status' => BookingStatus::ACCEPTED->value,
             ])
             ->assertSessionHas('error');

        $this->assertDatabaseHas('bookings', [
            'id'     => $booking->id,
            'status' => BookingStatus::REQUESTED->value, // unchanged
        ]);
    }

    // ─── Invalid State Jumps ──────────────────────────────────────────────────

    /** @test */
    public function cannot_jump_from_requested_to_completed(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makePublishedGig($mentor);

        $booking = Booking::factory()
            ->requested()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'proposed_date' => now()->addDays(3)->toDateString(),
                'proposed_time' => '10:00',
            ]);

        $this->actingAs($mentor)
             ->patch(route('bookings.status', $booking), [
                 'status' => BookingStatus::COMPLETED->value,
             ])
             ->assertSessionHasErrors('status'); // Invalid status transition fails form request validation

        $this->assertDatabaseHas('bookings', [
            'id'     => $booking->id,
            'status' => BookingStatus::REQUESTED->value,
        ]);
    }

    /** @test */
    public function cannot_transition_from_terminal_status_reviewed(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makePublishedGig($mentor);

        $booking = Booking::factory()
            ->reviewed()
            ->between($freelancer, $mentor, $gig)
            ->create();

        $this->actingAs($mentor)
             ->patch(route('bookings.status', $booking), [
                 'status' => BookingStatus::COMPLETED->value,
             ])
             ->assertSessionHasErrors('status'); // Invalid status transition fails form request validation
    }

    // ─── Cancellation ─────────────────────────────────────────────────────────

    /** @test */
    public function freelancer_can_cancel_an_accepted_booking(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makePublishedGig($mentor);

        $booking = Booking::factory()
            ->accepted()
            ->between($freelancer, $mentor, $gig)
            ->create();

        $this->actingAs($freelancer)
             ->post(route('bookings.cancel', $booking))
             ->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'id'     => $booking->id,
            'status' => BookingStatus::CANCELLED->value,
        ]);
    }

    /** @test */
    public function cannot_cancel_a_completed_booking(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makePublishedGig($mentor);

        $booking = Booking::factory()
            ->completed()
            ->between($freelancer, $mentor, $gig)
            ->create();

        $this->actingAs($freelancer)
             ->post(route('bookings.cancel', $booking))
             ->assertForbidden();
    }

    /** @test */
    public function third_party_user_cannot_cancel_someone_elses_booking(): void
    {
        $freelancer  = $this->makeFreelancer();
        $mentor      = $this->makeMentor();
        $gig         = $this->makePublishedGig($mentor);
        $thirdParty  = $this->makeFreelancer();

        $booking = Booking::factory()
            ->accepted()
            ->between($freelancer, $mentor, $gig)
            ->create();

        $this->actingAs($thirdParty)
             ->post(route('bookings.cancel', $booking))
             ->assertForbidden();
    }
}
