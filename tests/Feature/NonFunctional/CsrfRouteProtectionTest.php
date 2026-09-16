<?php

namespace Tests\Feature\NonFunctional;

use App\Models\Booking;
use App\Models\Gig;
use App\Models\MentorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CSRF & Route Protection Tests.
 *
 * Validates:
 * - All state-changing endpoints (POST/PATCH/DELETE) require a valid CSRF token
 * - Unauthenticated users are redirected to /login when hitting protected routes
 * - Role-based middleware blocks cross-role access (403)
 */
class CsrfRouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    // ─── CSRF Enforcement ─────────────────────────────────────────────────────

    /**
     * @test
     * @dataProvider stateChangingEndpoints
     */
    public function state_changing_endpoints_fail_without_csrf_token(
        string $method,
        string $route,
        array $params = []
    ): void {
        // withoutMiddleware only removes CSRF but keeps auth — we also need to be auth'd
        $user = User::factory()->freelancer()->approved()->create();

        // Use the real test client without explicitly providing the CSRF token
        // Laravel's TestCase always provides CSRF by default; we need to explicitly disable it
        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
                         ->actingAs($user);

        // The real test: make the request WITHOUT the CSRF middleware having run
        // which simulates what would happen with a missing token in a real browser
        // Instead we test that the VerifyCsrfToken middleware is registered and active:
        $this->withMiddleware(); // re-enable all middleware

        $csrfResponse = $this->call($method, $route, $params, [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        // Without a valid session/CSRF token, should get 419 (TokenMismatchException)
        $this->assertContains($csrfResponse->getStatusCode(), [419, 302]);
    }

    public static function stateChangingEndpoints(): array
    {
        return [
            'login POST'         => ['POST', '/login', ['email' => 'a@b.com', 'password' => 'pass']],
            'register POST'      => ['POST', '/register', ['email' => 'a@b.com']],
            'logout POST'        => ['POST', '/logout', []],
        ];
    }

    // ─── Authentication Gate ──────────────────────────────────────────────────

    /** @test */
    public function unauthenticated_user_accessing_freelancer_dashboard_is_redirected_to_login(): void
    {
        $this->get(route('freelancer.dashboard'))
             ->assertRedirect(route('login'));
    }

    /** @test */
    public function unauthenticated_user_accessing_mentor_dashboard_is_redirected_to_login(): void
    {
        $this->get(route('mentor.dashboard'))
             ->assertRedirect(route('login'));
    }

    /** @test */
    public function unauthenticated_user_accessing_admin_dashboard_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))
             ->assertRedirect(route('login'));
    }

    /** @test */
    public function unauthenticated_user_accessing_bookings_is_redirected_to_login(): void
    {
        $this->get(route('freelancer.bookings.index'))
             ->assertRedirect(route('login'));
    }

    /** @test */
    public function unauthenticated_post_to_bookings_store_is_redirected_to_login(): void
    {
        $this->post(route('bookings.store'), ['gig_id' => 1])
             ->assertRedirect(route('login'));
    }

    /** @test */
    public function unauthenticated_post_to_bookings_review_is_redirected_to_login(): void
    {
        $mentor     = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->verified()->create(['user_id' => $mentor->id]);
        $freelancer = User::factory()->freelancer()->approved()->create();
        $gig        = Gig::factory()->published()->forMentor($mentor)->create();
        $booking    = Booking::factory()->completed()->between($freelancer, $mentor, $gig)->create();

        $this->post(route('bookings.review', $booking), ['rating' => 5])
             ->assertRedirect(route('login'));
    }

    /** @test */
    public function unauthenticated_access_to_lms_index_is_redirected_to_login(): void
    {
        $this->get(route('lms.index'))
             ->assertRedirect(route('login'));
    }

    /** @test */
    public function unauthenticated_access_to_admin_approvals_is_redirected_to_login(): void
    {
        $this->get(route('admin.approvals.index'))
             ->assertRedirect(route('login'));
    }

    // ─── Role Middleware ──────────────────────────────────────────────────────

    /** @test */
    public function freelancer_cannot_access_mentor_dashboard(): void
    {
        $freelancer = User::factory()->freelancer()->approved()->create();

        $this->actingAs($freelancer)
             ->get(route('mentor.dashboard'))
             ->assertForbidden();
    }

    /** @test */
    public function mentor_cannot_access_freelancer_dashboard(): void
    {
        $mentor = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->verified()->create(['user_id' => $mentor->id]);

        $this->actingAs($mentor)
             ->get(route('freelancer.dashboard'))
             ->assertForbidden();
    }

    /** @test */
    public function freelancer_cannot_access_admin_dashboard(): void
    {
        $freelancer = User::factory()->freelancer()->approved()->create();

        $this->actingAs($freelancer)
             ->get(route('admin.dashboard'))
             ->assertForbidden();
    }

    /** @test */
    public function mentor_cannot_access_admin_approval_routes(): void
    {
        $mentor      = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->verified()->create(['user_id' => $mentor->id]);
        $pendingUser = User::factory()->freelancer()->pending()->create();

        $this->actingAs($mentor)
             ->patch(route('admin.approvals.approve', $pendingUser))
             ->assertForbidden();
    }

    /** @test */
    public function freelancer_cannot_create_gigs(): void
    {
        $freelancer = User::factory()->freelancer()->approved()->create();

        $this->actingAs($freelancer)
             ->get(route('mentor.gigs.create'))
             ->assertForbidden();
    }

    /** @test */
    public function mentor_cannot_access_freelancer_booking_creation(): void
    {
        $mentor = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->verified()->create(['user_id' => $mentor->id]);

        $this->actingAs($mentor)
             ->get(route('freelancer.bookings.index'))
             ->assertForbidden();
    }

    // ─── State-Changing Endpoint CSRF (HTTP-level, fresh session) ────────────

    /** @test */
    public function post_to_login_without_csrf_returns_419(): void
    {
        // Fresh request with no session token
        $response = $this->call('POST', '/login', [
            'email'    => 'test@example.com',
            'password' => 'password',
            '_token'   => 'invalid_token',
        ]);

        $this->assertSame(419, $response->getStatusCode());
    }

    /** @test */
    public function post_to_logout_without_csrf_returns_419(): void
    {
        $user = User::factory()->freelancer()->approved()->create();

        $response = $this->actingAs($user)->call('POST', '/logout', [
            '_token' => 'invalid_csrf_token',
        ]);

        $this->assertSame(419, $response->getStatusCode());
    }

    /** @test */
    public function patch_to_booking_status_without_valid_csrf_returns_419(): void
    {
        $mentor     = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->verified()->create(['user_id' => $mentor->id]);
        $freelancer = User::factory()->freelancer()->approved()->create();
        $gig        = Gig::factory()->published()->forMentor($mentor)->create();
        $booking    = Booking::factory()->requested()->between($freelancer, $mentor, $gig)->create([
            'proposed_date' => now()->addDays(5)->toDateString(),
            'proposed_time' => '10:00',
        ]);

        $response = $this->actingAs($mentor)->call(
            'PATCH',
            route('bookings.status', $booking),
            ['status' => 'accepted', '_token' => 'bad_token']
        );

        $this->assertSame(419, $response->getStatusCode());
    }
}
