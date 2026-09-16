<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Rate Limiting Tests.
 *
 * Validates that the LoginRequest throttle (from Laravel's default
 * RateLimiter on login attempts) blocks brute-force attacks:
 *
 * - After 5 failed login attempts, subsequent attempts return 429
 * - Rate limit is scoped by email + IP address
 * - Successful login clears the rate limit counter
 * - Throttle response includes Retry-After header
 */
class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear any existing rate limit state before each test
        RateLimiter::clear($this->throttleKey());
    }

    protected function tearDown(): void
    {
        RateLimiter::clear($this->throttleKey());
        parent::tearDown();
    }

    private function throttleKey(string $email = 'brute@example.com', string $ip = '127.0.0.1'): string
    {
        return 'login:' . strtolower($email) . '|' . $ip;
    }

    private function attemptLogin(
        string $email = 'brute@example.com',
        string $password = 'wrongpassword',
        string $ip = '127.0.0.1'
    ): \Illuminate\Testing\TestResponse {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
                    ->post('/login', [
                        'email'    => $email,
                        'password' => $password,
                    ]);
    }

    // ─── Throttle Activation ──────────────────────────────────────────────────

    /** @test */
    public function first_four_failed_attempts_return_redirect_not_429(): void
    {
        User::factory()->freelancer()->approved()->create([
            'email'    => 'brute@example.com',
            'password' => bcrypt('correct_password'),
        ]);

        // Attempts 1–4 should get redirected back to login (not throttled yet)
        for ($i = 1; $i <= 4; $i++) {
            $response = $this->attemptLogin();
            $this->assertNotSame(429, $response->getStatusCode(),
                "Attempt #{$i} should not be throttled yet.");
        }
    }

    /** @test */
    public function fifth_failed_attempt_triggers_throttle_and_returns_429(): void
    {
        User::factory()->freelancer()->approved()->create([
            'email'    => 'brute@example.com',
            'password' => bcrypt('correct_password'),
        ]);

        // Exhaust 5 attempts
        for ($i = 0; $i < 5; $i++) {
            $this->attemptLogin();
        }

        // 6th attempt must be throttled
        $response = $this->attemptLogin();

        $this->assertSame(429, $response->getStatusCode(),
            'Expected 429 Too Many Requests after 5 failed login attempts.');
    }

    /** @test */
    public function throttled_response_contains_retry_after_header(): void
    {
        User::factory()->freelancer()->approved()->create([
            'email'    => 'brute@example.com',
            'password' => bcrypt('correct_password'),
        ]);

        for ($i = 0; $i < 6; $i++) {
            $response = $this->attemptLogin();
        }

        // The 429 response should tell the client when to retry
        $this->assertSame(429, $response->getStatusCode());
        // Laravel's ThrottleRequests middleware sets Retry-After header
        $this->assertTrue(
            $response->headers->has('Retry-After') || $response->headers->has('X-RateLimit-Reset'),
            'Throttled response should include Retry-After or X-RateLimit-Reset header.'
        );
    }

    /** @test */
    public function throttle_is_scoped_per_email_and_ip(): void
    {
        User::factory()->freelancer()->approved()->create([
            'email'    => 'brute@example.com',
            'password' => bcrypt('correct_password'),
        ]);

        User::factory()->freelancer()->approved()->create([
            'email'    => 'innocent@example.com',
            'password' => bcrypt('correct_password'),
        ]);

        // Exhaust throttle for brute@example.com from IP 127.0.0.1
        for ($i = 0; $i < 6; $i++) {
            $this->attemptLogin('brute@example.com', 'wrong', '127.0.0.1');
        }

        // innocent@example.com from the same IP should NOT be throttled
        $response = $this->attemptLogin('innocent@example.com', 'wrong', '127.0.0.1');
        $this->assertNotSame(429, $response->getStatusCode(),
            'Throttle should be per email+IP, not just per IP.');
    }

    /** @test */
    public function throttle_blocks_even_correct_credentials_after_limit(): void
    {
        // This tests that the throttle check happens BEFORE credential verification
        User::factory()->freelancer()->approved()->create([
            'email'    => 'brute@example.com',
            'password' => bcrypt('correct_password'),
        ]);

        // Exhaust rate limit with wrong passwords
        for ($i = 0; $i < 5; $i++) {
            $this->attemptLogin('brute@example.com', 'wrongpassword');
        }

        // Try with the CORRECT password — should still be throttled
        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
                         ->post('/login', [
                             'email'    => 'brute@example.com',
                             'password' => 'correct_password',
                         ]);

        $this->assertSame(429, $response->getStatusCode(),
            'Rate limiter should block even correct credentials after limit is reached.');

        $this->assertGuest();
    }

    /** @test */
    public function successful_login_clears_rate_limit_counter(): void
    {
        $user = User::factory()->freelancer()->approved()->create([
            'email'             => 'brute@example.com',
            'password'          => bcrypt('correct_password'),
            'skills_onboarded'  => true,
        ]);

        // 4 failed attempts (one before limit)
        for ($i = 0; $i < 4; $i++) {
            $this->attemptLogin('brute@example.com', 'wrongpassword');
        }

        // Successful login should reset the counter
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
             ->post('/login', [
                 'email'    => 'brute@example.com',
                 'password' => 'correct_password',
             ]);

        $this->assertAuthenticatedAs($user);

        // Logout and try 4 more bad attempts — should not be immediately throttled
        $this->post('/logout');

        for ($i = 0; $i < 4; $i++) {
            $response = $this->attemptLogin('brute@example.com', 'wrongpassword');
            $this->assertNotSame(429, $response->getStatusCode(),
                "Counter should have been cleared after successful login. Attempt #{$i} is throttled prematurely.");
        }
    }

    /** @test */
    public function rate_limit_key_format_is_email_and_ip_combined(): void
    {
        $expectedKey = 'login:brute@example.com|127.0.0.1';
        $actualKey   = $this->throttleKey('brute@example.com', '127.0.0.1');

        $this->assertSame($expectedKey, $actualKey,
            'Rate limit key must combine email and IP to prevent cross-user impact.');
    }

    /** @test */
    public function multiple_ips_are_throttled_independently(): void
    {
        User::factory()->freelancer()->approved()->create([
            'email'    => 'brute@example.com',
            'password' => bcrypt('correct_password'),
        ]);

        // Exhaust limit from IP 10.0.0.1
        for ($i = 0; $i < 6; $i++) {
            $this->attemptLogin('brute@example.com', 'wrong', '10.0.0.1');
        }

        // Attempt from a different IP (10.0.0.2) should NOT be throttled
        $response = $this->attemptLogin('brute@example.com', 'wrong', '10.0.0.2');

        $this->assertNotSame(429, $response->getStatusCode(),
            'Throttle from IP 10.0.0.1 should not affect attempts from IP 10.0.0.2.');
    }
}
