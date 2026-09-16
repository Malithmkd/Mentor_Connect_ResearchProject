<?php

namespace Tests\Feature\Functional;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Gig;
use App\Models\MentorProfile;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Review Submission & Rating Aggregation Tests.
 *
 * Validates:
 * - Cannot review a non-completed booking
 * - Duplicate review prevention (unique booking_id + reviewer_id)
 * - Rating recalculation on users, mentor_profiles, and gigs
 * - Floating-point boundary cases (1-star, 5-star, mixed averages)
 */
class ReviewRatingAggregationTest extends TestCase
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
        MentorProfile::factory()->verified()->create([
            'user_id'        => $mentor->id,
            'average_rating' => 0.0,
            'total_reviews'  => 0,
        ]);
        return $mentor;
    }

    private function makeGig(User $mentor): Gig
    {
        return Gig::factory()->published()->forMentor($mentor)->create([
            'average_rating' => 0.0,
        ]);
    }

    private function makeCompletedBooking(User $freelancer, User $mentor, Gig $gig): Booking
    {
        return Booking::factory()
            ->completed()
            ->between($freelancer, $mentor, $gig)
            ->create();
    }

    // ─── Guard: Cannot Review Non-Completed Booking ───────────────────────────

    /** @test */
    public function cannot_review_a_requested_booking(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makeGig($mentor);

        $booking = Booking::factory()
            ->requested()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'proposed_date' => now()->addDays(5)->toDateString(),
                'proposed_time' => '10:00',
            ]);

        $this->actingAs($freelancer)
             ->post(route('bookings.review', $booking), [
                 'booking_id' => $booking->id,
                 'rating'  => 5,
                 'comment' => 'Great!',
             ])
             ->assertForbidden();

        $this->assertDatabaseMissing('reviews', ['booking_id' => $booking->id]);
    }

    /** @test */
    public function cannot_review_an_accepted_booking(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makeGig($mentor);

        $booking = Booking::factory()
            ->accepted()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'proposed_date' => now()->addDays(3)->toDateString(),
                'proposed_time' => '10:00',
            ]);

        $this->actingAs($freelancer)
             ->post(route('bookings.review', $booking), [
                 'booking_id' => $booking->id,
                 'rating'  => 4,
                 'comment' => 'Nice!',
             ])
             ->assertForbidden();
    }

    /** @test */
    public function cannot_review_a_cancelled_booking(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makeGig($mentor);

        $booking = Booking::factory()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'status'     => BookingStatus::CANCELLED,
                'proposed_date' => now()->subDay()->toDateString(),
                'proposed_time' => '10:00',
            ]);

        $this->actingAs($freelancer)
             ->post(route('bookings.review', $booking), [
                 'booking_id' => $booking->id,
                 'rating' => 3,
             ])
             ->assertForbidden();
    }

    // ─── Duplicate Review Prevention ──────────────────────────────────────────

    /** @test */
    public function freelancer_cannot_submit_duplicate_review_for_same_booking(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makeGig($mentor);

        $booking = $this->makeCompletedBooking($freelancer, $mentor, $gig);

        // First review succeeds
        $this->actingAs($freelancer)
             ->post(route('bookings.review', $booking), [
                 'booking_id' => $booking->id,
                 'rating'  => 5,
                 'comment' => 'First review.',
             ])
             ->assertSessionHas('success');

        // Booking is now REVIEWED — canBeReviewedBy returns false (already reviewed)
        $this->actingAs($freelancer)
             ->post(route('bookings.review', $booking->fresh()), [
                 'booking_id' => $booking->id,
                 'rating'  => 1,
                 'comment' => 'Duplicate attempt.',
             ])
             ->assertForbidden();

        $this->assertSame(1, Review::where('booking_id', $booking->id)->count());
    }

    // ─── Rating Recalculation ─────────────────────────────────────────────────

    /** @test */
    public function gig_average_rating_is_recalculated_after_review(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makeGig($mentor);

        $booking = $this->makeCompletedBooking($freelancer, $mentor, $gig);

        $this->actingAs($freelancer)
             ->post(route('bookings.review', $booking), [
                 'booking_id' => $booking->id,
                 'rating'  => 4,
                 'comment' => 'Good session.',
             ]);

        $this->assertDatabaseHas('gigs', [
            'id'             => $gig->id,
            'average_rating' => 4.0,
        ]);
    }

    /** @test */
    public function mentor_profile_average_rating_is_recalculated_after_review(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makeGig($mentor);

        $booking = $this->makeCompletedBooking($freelancer, $mentor, $gig);

        $this->actingAs($freelancer)
             ->post(route('bookings.review', $booking), [
                 'booking_id' => $booking->id,
                 'rating' => 5,
             ]);

        $this->assertDatabaseHas('mentor_profiles', [
            'user_id'        => $mentor->id,
            'average_rating' => 5.0,
            'total_reviews'  => 1,
        ]);
    }

    /** @test */
    public function mentor_user_average_rating_is_recalculated_after_review(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makeGig($mentor);

        $booking = $this->makeCompletedBooking($freelancer, $mentor, $gig);

        $this->actingAs($freelancer)
             ->post(route('bookings.review', $booking), [
                 'booking_id' => $booking->id,
                 'rating' => 3,
             ]);

        $this->assertDatabaseHas('users', [
            'id'             => $mentor->id,
            'average_rating' => 3.0,
            'total_reviews'  => 1,
        ]);
    }

    /** @test */
    public function average_rating_is_correctly_recalculated_across_multiple_reviews(): void
    {
        $mentor  = $this->makeMentor();
        $gig     = $this->makeGig($mentor);

        // Ratings: 5, 3, 4 → Average = 4.0
        $ratings = [5, 3, 4];

        foreach ($ratings as $rating) {
            $freelancer = $this->makeFreelancer();
            $booking    = $this->makeCompletedBooking($freelancer, $mentor, $gig);

            $this->actingAs($freelancer)
                 ->post(route('bookings.review', $booking), [
                     'booking_id' => $booking->id,
                     'rating' => $rating
                 ]);
        }

        $this->assertDatabaseHas('gigs', [
            'id'             => $gig->id,
            'average_rating' => 4.0, // (5+3+4)/3 = 4.0
        ]);
    }

    /** @test */
    public function one_star_review_correctly_sets_rating_to_one(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makeGig($mentor);
        $booking    = $this->makeCompletedBooking($freelancer, $mentor, $gig);

        $this->actingAs($freelancer)
             ->post(route('bookings.review', $booking), ['rating' => 1]);

        $this->assertDatabaseHas('gigs', [
            'id'             => $gig->id,
            'average_rating' => 1.0,
        ]);
        $this->assertDatabaseHas('users', [
            'id'             => $mentor->id,
            'average_rating' => 1.0,
        ]);
    }

    /** @test */
    public function five_star_review_correctly_sets_rating_to_five(): void
    {
        $freelancer = $this->makeFreelancer();
        $mentor     = $this->makeMentor();
        $gig        = $this->makeGig($mentor);
        $booking    = $this->makeCompletedBooking($freelancer, $mentor, $gig);

        $this->actingAs($freelancer)
             ->post(route('bookings.review', $booking), ['rating' => 5]);

        $this->assertDatabaseHas('gigs', [
            'id'             => $gig->id,
            'average_rating' => 5.0,
        ]);
    }

    /** @test */
    public function mixed_one_and_five_star_reviews_average_to_three(): void
    {
        $mentor  = $this->makeMentor();
        $gig     = $this->makeGig($mentor);

        $freelancer1 = $this->makeFreelancer();
        $booking1    = $this->makeCompletedBooking($freelancer1, $mentor, $gig);
        $this->actingAs($freelancer1)->post(route('bookings.review', $booking1), ['rating' => 1]);

        $freelancer2 = $this->makeFreelancer();
        $booking2    = $this->makeCompletedBooking($freelancer2, $mentor, $gig);
        $this->actingAs($freelancer2)->post(route('bookings.review', $booking2), ['rating' => 5]);

        // (1 + 5) / 2 = 3.0
        $this->assertDatabaseHas('gigs', [
            'id'             => $gig->id,
            'average_rating' => 3.0,
        ]);
    }
}
