<?php

namespace Tests\Feature\Security;

use App\Models\Gig;
use App\Models\MentorProfile;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SQL Injection & Input Sanitization Tests.
 *
 * Validates that:
 * - Search/filter endpoints are immune to SQL injection payloads
 * - Skill ID arrays are cast to integers (array_map('intval', ...))
 * - Malicious input does not cause errors or data leakage
 * - Eloquent parameterized queries protect against injection
 */
class InjectionSanitizationTest extends TestCase
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

    // ─── Gig Search SQL Injection ─────────────────────────────────────────────

    /**
     * @test
     * @dataProvider sqlInjectionPayloads
     */
    public function gig_search_is_immune_to_sql_injection(string $payload): void
    {
        $freelancer = $this->makeFreelancer();

        // Create some legitimate gigs to ensure the DB is not emptied by injection
        Gig::factory()->count(3)->published()->forMentor($this->makeMentor())->create();

        $response = $this->actingAs($freelancer)
                         ->get(route('gigs.index', ['search' => $payload]));

        // Should always return 200 (not 500 from SQL error)
        $response->assertStatus(200);
    }

    public static function sqlInjectionPayloads(): array
    {
        return [
            "classic OR"         => ["' OR '1'='1"],
            "comment injection"  => ["'; DROP TABLE gigs; --"],
            "union select"       => ["' UNION SELECT null, null, null --"],
            "blind timing"       => ["1' AND SLEEP(5) --"],
            "hex encoding"       => ["0x27 OR 1=1"],
            "stacked queries"    => ["1; DELETE FROM users; --"],
            "subquery injection" => ["' OR (SELECT COUNT(*) FROM users) > 0 --"],
            "always true"        => ["' OR 'a'='a"],
        ];
    }

    // ─── Search Filter: Experience Level ─────────────────────────────────────

    /** @test */
    public function invalid_experience_level_filter_does_not_cause_sql_error(): void
    {
        $freelancer = $this->makeFreelancer();

        $response = $this->actingAs($freelancer)
                         ->get(route('gigs.index', ['experience' => "' OR 1=1 --"]));

        $response->assertStatus(200);
    }

    // ─── Skill ID Sanitization ────────────────────────────────────────────────

    /** @test */
    public function skill_ids_in_filter_are_cast_to_integers_preventing_injection(): void
    {
        $freelancer = $this->makeFreelancer();
        $skills     = Skill::factory()->count(3)->create();

        // Mix valid IDs with injection strings
        $skillParam = implode(',', array_merge(
            $skills->pluck('id')->toArray(),
            ["1 OR 1=1", "0; DROP TABLE skills;--"]
        ));

        $response = $this->actingAs($freelancer)
                         ->get(route('gigs.index', ['skills' => $skillParam]));

        // Should succeed without error (invalid strings cast to 0 via intval)
        $response->assertStatus(200);
    }

    /** @test */
    public function skill_onboarding_with_non_integer_ids_is_rejected_or_sanitized(): void
    {
        $user   = User::factory()->freelancer()->approved()->unonboarded()->create();
        $skill1 = Skill::factory()->create();

        $response = $this->actingAs($user)
                         ->post(route('onboarding.skills.store'), [
                             'skills' => [$skill1->id, "1; DROP TABLE skills", "' OR 1=1"],
                         ]);

        // Should succeed or fail gracefully — no DB error
        $this->assertNotSame(500, $response->getStatusCode(),
            'Skill onboarding with injection payload caused a 500 server error.');
    }

    // ─── Auth Inputs ──────────────────────────────────────────────────────────

    /** @test */
    public function login_with_sql_injection_in_email_does_not_authenticate(): void
    {
        User::factory()->freelancer()->approved()->create([
            'email'    => 'real@user.com',
            'password' => bcrypt('Password1!'),
        ]);

        $response = $this->post('/login', [
            'email'    => "' OR '1'='1' --",
            'password' => 'anything',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function login_with_sql_injection_in_password_does_not_authenticate(): void
    {
        User::factory()->freelancer()->approved()->create([
            'email'    => 'victim@user.com',
            'password' => bcrypt('real_password'),
        ]);

        $response = $this->post('/login', [
            'email'    => 'victim@user.com',
            'password' => "' OR '1'='1",
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function registration_email_with_injection_is_rejected_by_validation(): void
    {
        $response = $this->post('/register', [
            'first_name'            => 'Test',
            'last_name'             => 'User',
            'email'                 => "test'; DROP TABLE users;--@example.com",
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role'                  => 'freelancer',
        ]);

        // Email validation should reject non-valid email format
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    // ─── Booking Notes Injection ──────────────────────────────────────────────

    /** @test */
    public function booking_notes_with_sql_injection_are_stored_as_plain_text(): void
    {
        $mentor     = $this->makeMentor();
        $freelancer = $this->makeFreelancer();
        $gig        = Gig::factory()->published()->forMentor($mentor)->withDuration(60)->create();

        $booking = \App\Models\Booking::factory()
            ->requested()
            ->between($freelancer, $mentor, $gig)
            ->create([
                'proposed_date' => now()->addDays(3)->toDateString(),
                'proposed_time' => '10:00',
            ]);

        $injectionPayload = "Note'; DROP TABLE bookings; --";

        $this->actingAs($freelancer)
             ->post(route('bookings.storeNote', $booking), [
                 'note' => $injectionPayload,
             ])
             ->assertSessionHasNoErrors();

        // Payload should be stored as-is (escaped by Eloquent), not executed
        $this->assertDatabaseHas('booking_notes', [
            'booking_id' => $booking->id,
            'note'       => $injectionPayload,
        ]);
    }
}
