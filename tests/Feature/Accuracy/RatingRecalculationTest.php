<?php

namespace Tests\Feature\Accuracy;

use App\Models\Booking;
use App\Models\Gig;
use App\Models\MentorProfile;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rating Recalculation Accuracy Tests.
 *
 * Validates floating-point arithmetic precision for AVG(rating) updates
 * on mentor_profiles, gigs, and users after:
 * - Single reviews (1-star, 5-star)
 * - Multiple reviews with known averages
 * - Boundary cases: single review, uniform ratings, alternating min/max
 *
 * Uses the review submission endpoint to trigger the recalculation logic
 * in BookingController::review().
 */
class RatingRecalculationTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeMentorWithProfile(): array
    {
        $mentor  = User::factory()->mentor()->approved()->create(['average_rating' => 0.0, 'total_reviews' => 0]);
        $profile = MentorProfile::factory()->verified()->create([
            'user_id'        => $mentor->id,
            'average_rating' => 0.0,
            'total_reviews'  => 0,
        ]);
        return [$mentor, $profile];
    }

    private function makeCompletedBookingAndSubmitReview(
        User $freelancer,
        User $mentor,
        Gig $gig,
        int $rating
    ): void {
        $booking = Booking::factory()
            ->completed()
            ->between($freelancer, $mentor, $gig)
            ->create();

        $this->actingAs($freelancer)
             ->post(route('bookings.review', $booking), [
                 'rating'    => $rating,
                 'comment'   => "Rating: {$rating}",
                 'is_public' => true,
             ]);
    }

    // ─── Single-review Accuracy ───────────────────────────────────────────────

    /** @test */
    public function single_one_star_review_sets_average_to_1_0(): void
    {
        [$mentor, $profile] = $this->makeMentorWithProfile();
        $freelancer = User::factory()->freelancer()->approved()->create();
        $gig        = Gig::factory()->published()->forMentor($mentor)->create(['average_rating' => 0.0]);

        $this->makeCompletedBookingAndSubmitReview($freelancer, $mentor, $gig, 1);

        $this->assertSame('1.0', number_format($gig->fresh()->average_rating, 1));
        $this->assertSame('1.0', number_format($mentor->fresh()->average_rating, 1));
        $this->assertSame('1.0', number_format($profile->fresh()->average_rating, 1));
        $this->assertSame(1, $profile->fresh()->total_reviews);
    }

    /** @test */
    public function single_five_star_review_sets_average_to_5_0(): void
    {
        [$mentor, $profile] = $this->makeMentorWithProfile();
        $freelancer = User::factory()->freelancer()->approved()->create();
        $gig        = Gig::factory()->published()->forMentor($mentor)->create(['average_rating' => 0.0]);

        $this->makeCompletedBookingAndSubmitReview($freelancer, $mentor, $gig, 5);

        $this->assertSame('5.0', number_format($gig->fresh()->average_rating, 1));
        $this->assertSame('5.0', number_format($mentor->fresh()->average_rating, 1));
        $this->assertSame('5.0', number_format($profile->fresh()->average_rating, 1));
        $this->assertSame(1, $profile->fresh()->total_reviews);
    }

    // ─── Multi-review Precision ───────────────────────────────────────────────

    /** @test */
    public function average_of_1_and_5_is_3_0(): void
    {
        [$mentor] = $this->makeMentorWithProfile();
        $gig      = Gig::factory()->published()->forMentor($mentor)->create(['average_rating' => 0.0]);

        $freelancer1 = User::factory()->freelancer()->approved()->create();
        $freelancer2 = User::factory()->freelancer()->approved()->create();

        $this->makeCompletedBookingAndSubmitReview($freelancer1, $mentor, $gig, 1);
        $this->makeCompletedBookingAndSubmitReview($freelancer2, $mentor, $gig, 5);

        // (1 + 5) / 2 = 3.0
        $this->assertSame('3.0', number_format($gig->fresh()->average_rating, 1));
        $this->assertSame('3.0', number_format($mentor->fresh()->average_rating, 1));
    }

    /** @test */
    public function average_of_5_4_3_is_4_0(): void
    {
        [$mentor] = $this->makeMentorWithProfile();
        $gig      = Gig::factory()->published()->forMentor($mentor)->create(['average_rating' => 0.0]);

        foreach ([5, 4, 3] as $rating) {
            $freelancer = User::factory()->freelancer()->approved()->create();
            $this->makeCompletedBookingAndSubmitReview($freelancer, $mentor, $gig, $rating);
        }

        // (5 + 4 + 3) / 3 = 4.0
        $this->assertSame('4.0', number_format($gig->fresh()->average_rating, 1));
    }

    /** @test */
    public function average_of_5_ratings_of_3_is_3_0(): void
    {
        [$mentor] = $this->makeMentorWithProfile();
        $gig      = Gig::factory()->published()->forMentor($mentor)->create(['average_rating' => 0.0]);

        foreach (range(1, 5) as $i) {
            $freelancer = User::factory()->freelancer()->approved()->create();
            $this->makeCompletedBookingAndSubmitReview($freelancer, $mentor, $gig, 3);
        }

        // 5 × 3 / 5 = 3.0
        $this->assertSame('3.0', number_format($gig->fresh()->average_rating, 1));
    }

    /** @test */
    public function average_of_1_2_3_4_5_is_3_0(): void
    {
        [$mentor] = $this->makeMentorWithProfile();
        $gig      = Gig::factory()->published()->forMentor($mentor)->create(['average_rating' => 0.0]);

        foreach ([1, 2, 3, 4, 5] as $rating) {
            $freelancer = User::factory()->freelancer()->approved()->create();
            $this->makeCompletedBookingAndSubmitReview($freelancer, $mentor, $gig, $rating);
        }

        // (1+2+3+4+5)/5 = 3.0
        $this->assertSame('3.0', number_format($gig->fresh()->average_rating, 1));
    }

    /** @test */
    public function average_of_4_and_5_is_4_5(): void
    {
        [$mentor] = $this->makeMentorWithProfile();
        $gig      = Gig::factory()->published()->forMentor($mentor)->create(['average_rating' => 0.0]);

        $freelancer1 = User::factory()->freelancer()->approved()->create();
        $freelancer2 = User::factory()->freelancer()->approved()->create();

        $this->makeCompletedBookingAndSubmitReview($freelancer1, $mentor, $gig, 4);
        $this->makeCompletedBookingAndSubmitReview($freelancer2, $mentor, $gig, 5);

        // (4 + 5) / 2 = 4.5
        $this->assertSame('4.5', number_format($gig->fresh()->average_rating, 1));
    }

    /** @test */
    public function total_reviews_count_increments_correctly(): void
    {
        [$mentor, $profile] = $this->makeMentorWithProfile();
        $gig               = Gig::factory()->published()->forMentor($mentor)->create(['average_rating' => 0.0]);

        foreach (range(1, 4) as $i) {
            $freelancer = User::factory()->freelancer()->approved()->create();
            $this->makeCompletedBookingAndSubmitReview($freelancer, $mentor, $gig, $i);
        }

        $this->assertSame(4, $profile->fresh()->total_reviews);
        $this->assertSame(4, $mentor->fresh()->total_reviews);
    }

    /** @test */
    public function rating_is_rounded_to_one_decimal_place(): void
    {
        [$mentor] = $this->makeMentorWithProfile();
        $gig      = Gig::factory()->published()->forMentor($mentor)->create(['average_rating' => 0.0]);

        // Ratings: 1, 2 → avg = 1.5
        $freelancer1 = User::factory()->freelancer()->approved()->create();
        $freelancer2 = User::factory()->freelancer()->approved()->create();

        $this->makeCompletedBookingAndSubmitReview($freelancer1, $mentor, $gig, 1);
        $this->makeCompletedBookingAndSubmitReview($freelancer2, $mentor, $gig, 2);

        $gigAvg = $gig->fresh()->average_rating;

        // Should be stored as 1.5 (rounded to 1 decimal)
        $this->assertSame('1.5', number_format($gigAvg, 1));
    }

    /** @test */
    public function rating_recalculation_is_idempotent_after_the_same_reviewer_review(): void
    {
        [$mentor] = $this->makeMentorWithProfile();
        $gig      = Gig::factory()->published()->forMentor($mentor)->create(['average_rating' => 0.0]);

        $freelancer = User::factory()->freelancer()->approved()->create();
        $booking    = Booking::factory()->completed()->between($freelancer, $mentor, $gig)->create();

        // Submit one review
        $this->actingAs($freelancer)
             ->post(route('bookings.review', $booking), ['rating' => 4]);

        $ratingAfterFirst = $gig->fresh()->average_rating;
        $this->assertSame('4.0', number_format($ratingAfterFirst, 1));

        // No second review allowed (same booking + reviewer) — rating should stay at 4.0
        $this->actingAs($freelancer)
             ->post(route('bookings.review', $booking->fresh()), ['rating' => 1]);

        $this->assertSame('4.0', number_format($gig->fresh()->average_rating, 1));
    }
}
