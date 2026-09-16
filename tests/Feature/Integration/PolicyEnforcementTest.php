<?php

namespace Tests\Feature\Integration;

use App\Enums\BookingStatus;
use App\Enums\GigStatus;
use App\Models\Booking;
use App\Models\Gig;
use App\Models\MentorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Policy Enforcement Integration Tests.
 *
 * Tests BookingPolicy and GigPolicy across:
 * - Unauthorized roles (wrong role attempting resource access)
 * - Gig owners vs third parties
 * - Mentor vs freelancer policy differences
 * - Admin override access
 */
class PolicyEnforcementTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeFreelancer(): User
    {
        return User::factory()->freelancer()->approved()->create();
    }

    private function makeMentor(): User
    {
        $m = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->verified()->create(['user_id' => $m->id]);
        return $m;
    }

    private function makeAdmin(): User
    {
        return User::factory()->admin()->create();
    }

    private function makeGig(User $mentor, GigStatus $status = GigStatus::PUBLISHED): Gig
    {
        return Gig::factory()->forMentor($mentor)->create(['status' => $status]);
    }

    private function makeCompletedBooking(User $freelancer, User $mentor, Gig $gig): Booking
    {
        return Booking::factory()
            ->completed()
            ->between($freelancer, $mentor, $gig)
            ->create();
    }

    // ─── BookingPolicy: view ──────────────────────────────────────────────────

    /** @test */
    public function freelancer_can_view_their_own_booking(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makeGig($mentor);

        $booking = Booking::factory()
            ->requested()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'proposed_date' => now()->addDays(3)->toDateString(),
                'proposed_time' => '10:00',
            ]);

        $this->actingAs($freelancer)
             ->get(route('freelancer.bookings.show', $booking))
             ->assertOk();
    }

    /** @test */
    public function mentor_can_view_booking_they_are_part_of(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makeGig($mentor);

        $booking = Booking::factory()
            ->requested()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'proposed_date' => now()->addDays(3)->toDateString(),
                'proposed_time' => '10:00',
            ]);

        $this->actingAs($mentor)
             ->get(route('mentor.bookings.show', $booking))
             ->assertOk();
    }

    /** @test */
    public function third_party_freelancer_cannot_view_someone_elses_booking(): void
    {
        $freelancer  = $this->makeFreelancer();
        $thirdParty  = $this->makeFreelancer();
        $mentor      = $this->makeMentor();
        $gig         = $this->makeGig($mentor);

        $booking = Booking::factory()
            ->requested()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'proposed_date' => now()->addDays(3)->toDateString(),
                'proposed_time' => '10:00',
            ]);

        $this->actingAs($thirdParty)
             ->get(route('freelancer.bookings.show', $booking))
             ->assertForbidden();
    }

    /** @test */
    public function admin_can_view_any_booking(): void
    {
        $admin      = $this->makeAdmin();
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makeGig($mentor);

        $booking = Booking::factory()
            ->requested()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'proposed_date' => now()->addDays(3)->toDateString(),
                'proposed_time' => '10:00',
            ]);

        // Admin is routed to freelancer.bookings.show (uses generic show controller)
        $this->actingAs($admin)
             ->get(route('freelancer.bookings.show', $booking))
             ->assertOk();
    }

    // ─── BookingPolicy: create ─────────────────────────────────────────────────

    /** @test */
    public function mentor_cannot_create_a_booking_request(): void
    {
        $mentor      = $this->makeMentor();
        $otherMentor = $this->makeMentor();
        $gig         = $this->makeGig($otherMentor);

        $this->actingAs($mentor)
             ->post(route('bookings.store'), [
                 'gig_id'        => $gig->id,
                 'proposed_date' => now()->addDays(5)->toDateString(),
                 'proposed_time' => '10:00',
             ])
             ->assertForbidden();
    }

    // ─── BookingPolicy: update ─────────────────────────────────────────────────

    /** @test */
    public function freelancer_can_update_their_draft_booking(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makeGig($mentor);

        $booking = Booking::factory()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'status'        => BookingStatus::REQUESTED,
                'proposed_date' => now()->addDays(5)->toDateString(),
                'proposed_time' => '10:00',
                'requested_at'  => now(),
            ]);

        // The update policy for REQUESTED status and freelancer owner = allowed
        $this->assertTrue(
            (new \App\Policies\BookingPolicy())->update($freelancer, $booking)
        );
    }

    /** @test */
    public function freelancer_cannot_update_a_completed_booking(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makeGig($mentor);

        $booking = Booking::factory()
            ->completed()
            ->between($freelancer, $mentor, $gig)
            ->create();

        $this->assertFalse(
            (new \App\Policies\BookingPolicy())->update($freelancer, $booking)
        );
    }

    // ─── BookingPolicy: cancel ─────────────────────────────────────────────────

    /** @test */
    public function cancel_policy_returns_false_for_requested_status(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makeGig($mentor);

        $booking = Booking::factory()
            ->requested()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'proposed_date' => now()->addDays(3)->toDateString(),
                'proposed_time' => '10:00',
            ]);

        // canCancel() is false for REQUESTED status
        $this->assertFalse(
            (new \App\Policies\BookingPolicy())->cancel($freelancer, $booking)
        );
    }

    // ─── GigPolicy ────────────────────────────────────────────────────────────

    /** @test */
    public function only_verified_mentor_can_create_gig(): void
    {
        $mentor   = $this->makeMentor(); // verified by default
        $unverified = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->pending()->create(['user_id' => $unverified->id]);

        $this->assertTrue((new \App\Policies\GigPolicy())->create($mentor));
        $this->assertFalse((new \App\Policies\GigPolicy())->create($unverified));
    }

    /** @test */
    public function freelancer_cannot_create_gig(): void
    {
        $freelancer = $this->makeFreelancer();

        $this->actingAs($freelancer)
             ->get(route('mentor.gigs.create'))
             ->assertForbidden();
    }

    /** @test */
    public function gig_owner_can_update_their_own_gig(): void
    {
        $mentor = $this->makeMentor();
        $gig    = $this->makeGig($mentor);

        $this->assertTrue((new \App\Policies\GigPolicy())->update($mentor, $gig));
    }

    /** @test */
    public function other_mentor_cannot_update_someone_elses_gig(): void
    {
        $mentor1 = $this->makeMentor();
        $mentor2 = $this->makeMentor();
        $gig     = $this->makeGig($mentor1);

        $this->assertFalse((new \App\Policies\GigPolicy())->update($mentor2, $gig));

        $this->actingAs($mentor2)
             ->patch(route('mentor.gigs.update', $gig), [
                 'title' => 'Hacked Title',
             ])
             ->assertForbidden();
    }

    /** @test */
    public function admin_can_update_any_gig(): void
    {
        $admin  = $this->makeAdmin();
        $mentor = $this->makeMentor();
        $gig    = $this->makeGig($mentor);

        $this->assertTrue((new \App\Policies\GigPolicy())->update($admin, $gig));
    }

    /** @test */
    public function gig_view_policy_allows_published_gig_without_auth(): void
    {
        $mentor = $this->makeMentor();
        $gig    = $this->makeGig($mentor, GigStatus::PUBLISHED);

        $this->assertTrue((new \App\Policies\GigPolicy())->view(null, $gig));
    }

    /** @test */
    public function gig_view_policy_denies_draft_gig(): void
    {
        $mentor = $this->makeMentor();
        $gig    = $this->makeGig($mentor, GigStatus::DRAFT);

        $this->assertFalse((new \App\Policies\GigPolicy())->view(null, $gig));
    }

    /** @test */
    public function gig_change_status_is_only_allowed_for_owner(): void
    {
        $mentor1 = $this->makeMentor();
        $mentor2 = $this->makeMentor();
        $gig     = $this->makeGig($mentor1);

        $this->assertTrue((new \App\Policies\GigPolicy())->changeStatus($mentor1, $gig));
        $this->assertFalse((new \App\Policies\GigPolicy())->changeStatus($mentor2, $gig));
    }

    // ─── BookingPolicy: complete ──────────────────────────────────────────────

    /** @test */
    public function only_mentor_or_admin_can_complete_a_scheduled_booking(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $admin      = $this->makeAdmin();
        $gig        = Gig::factory()->published()->forMentor($mentor)->withDuration(60)->create();

        $booking = Booking::factory()
            ->scheduled()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'proposed_date' => now()->subDay()->toDateString(),
                'proposed_time' => '08:00', // clearly in the past
            ]);

        $policy = new \App\Policies\BookingPolicy();

        $this->assertFalse($policy->complete($freelancer, $booking->fresh()));
        $this->assertTrue($policy->complete($mentor, $booking->fresh()));
        $this->assertTrue($policy->complete($admin, $booking->fresh()));
    }

    // ─── BookingPolicy: review ─────────────────────────────────────────────────

    /** @test */
    public function review_policy_allows_both_parties_to_review_completed_booking(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makeGig($mentor);

        $booking = Booking::factory()
            ->completed()
            ->between($freelancer, $mentor, $gig)
            ->create();

        $policy = new \App\Policies\BookingPolicy();

        $this->assertTrue($policy->review($freelancer, $booking));
        $this->assertTrue($policy->review($mentor, $booking));
    }

    /** @test */
    public function review_policy_denies_after_user_already_reviewed(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makeGig($mentor);

        $booking = Booking::factory()
            ->completed()
            ->between($freelancer, $mentor, $gig)
            ->create();

        // Freelancer submits a review
        $booking->reviews()->create([
            'reviewer_id'   => $freelancer->id,
            'reviewee_id'   => $mentor->id,
            'freelancer_id' => $freelancer->id,
            'mentor_id'     => $mentor->id,
            'gig_id'        => $gig->id,
            'rating'        => 5,
        ]);

        $policy = new \App\Policies\BookingPolicy();

        // Now freelancer cannot review again
        $this->assertFalse($policy->review($freelancer, $booking->fresh()));
        // Mentor can still review
        $this->assertTrue($policy->review($mentor, $booking->fresh()));
    }
}
