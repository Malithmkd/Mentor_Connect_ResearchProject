<?php

namespace Tests\Feature\Integration;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Gig;
use App\Models\MentorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AuditLog Integration Tests.
 *
 * Asserts that AuditLog::log() is called on critical system events
 * and that the resulting DB records contain expected fields:
 * - event, area, user_id, description
 * - old_values / new_values JSON payloads
 * - ip_address recorded
 */
class AuditLogIntegrationTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeApprovedFreelancer(): User
    {
        return User::factory()->freelancer()->approved()->create([
            'password' => bcrypt('password'),
        ]);
    }

    private function makeApprovedMentor(): User
    {
        $mentor = User::factory()->mentor()->approved()->create([
            'password'         => bcrypt('password'),
            'skills_onboarded' => true,
        ]);
        MentorProfile::factory()->verified()->create(['user_id' => $mentor->id]);
        return $mentor;
    }

    // ─── auth.login ───────────────────────────────────────────────────────────

    /** @test */
    public function auth_login_event_is_logged_on_successful_login(): void
    {
        $user = User::factory()->freelancer()->approved()->create([
            'password'         => bcrypt('password'),
            'skills_onboarded' => true,
        ]);

        $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event'   => 'auth.login',
            'area'    => 'auth',
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function auth_login_audit_log_records_ip_address(): void
    {
        $user = User::factory()->freelancer()->approved()->create([
            'password'         => bcrypt('password'),
            'skills_onboarded' => true,
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
             ->post('/login', [
                 'email'    => $user->email,
                 'password' => 'password',
             ]);

        $log = AuditLog::where('event', 'auth.login')
                       ->where('user_id', $user->id)
                       ->first();

        $this->assertNotNull($log);
        $this->assertSame('192.168.1.100', $log->ip_address);
    }

    // ─── user.registered ─────────────────────────────────────────────────────

    /** @test */
    public function user_registered_event_is_logged_on_registration(): void
    {
        $this->post('/register', [
            'first_name'            => 'Test',
            'last_name'             => 'User',
            'email'                 => 'newuser@example.com',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role'                  => 'freelancer',
        ]);

        $log = AuditLog::where('event', 'user.registered')->first();

        $this->assertNotNull($log);
        $this->assertSame('auth', $log->area);
        $this->assertArrayHasKey('email', $log->new_values);
        $this->assertArrayHasKey('role', $log->new_values);
        $this->assertArrayHasKey('account_status', $log->new_values);
        $this->assertSame('pending', $log->new_values['account_status']);
    }

    // ─── booking.created ─────────────────────────────────────────────────────

    /** @test */
    public function booking_created_event_is_logged_when_freelancer_requests_booking(): void
    {
        $freelancer = $this->makeApprovedFreelancer();
        $mentor     = $this->makeApprovedMentor();
        $gig        = Gig::factory()->published()->forMentor($mentor)->withDuration(60)->create();

        $this->actingAs($freelancer)->post(route('bookings.store'), [
            'gig_id'        => $gig->id,
            'proposed_date' => now()->addDays(7)->toDateString(),
            'proposed_time' => '10:00',
        ]);

        $log = AuditLog::where('event', 'booking.created')
                       ->where('user_id', $freelancer->id)
                       ->first();

        $this->assertNotNull($log, 'Expected audit log entry for booking.created');
        $this->assertSame('bookings', $log->area);
        $this->assertArrayHasKey('gig_id', $log->new_values);
        $this->assertArrayHasKey('price_paid', $log->new_values);
        $this->assertSame($gig->id, $log->new_values['gig_id']);
    }

    // ─── user.approved (via Admin Approval) ──────────────────────────────────

    /** @test */
    public function user_approved_event_is_logged_when_admin_approves_user(): void
    {
        $admin       = User::factory()->admin()->create();
        $pendingUser = User::factory()->freelancer()->pending()->create();

        $this->actingAs($admin)
             ->patch(route('admin.approvals.approve', $pendingUser));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'user.approved',
            'area'  => 'approvals',
        ]);
    }

    /** @test */
    public function user_rejected_event_is_logged_when_admin_rejects_user(): void
    {
        $admin       = User::factory()->admin()->create();
        $pendingUser = User::factory()->freelancer()->pending()->create();

        $this->actingAs($admin)
             ->patch(route('admin.approvals.reject', $pendingUser), [
                 'rejection_reason' => 'Incomplete information.',
             ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'user.rejected',
            'area'  => 'approvals',
        ]);
    }

    // ─── AuditLog::log() static helper ───────────────────────────────────────

    /** @test */
    public function audit_log_static_log_method_creates_record_correctly(): void
    {
        $user = User::factory()->freelancer()->approved()->create();

        $this->actingAs($user);

        AuditLog::log(
            'custom.event',
            'Test description',
            'system',
            $user,
            ['old_field' => 'old_value'],
            ['new_field' => 'new_value']
        );

        $log = AuditLog::where('event', 'custom.event')->first();

        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('Test description', $log->description);
        $this->assertSame('system', $log->area);
        $this->assertSame(get_class($user), $log->auditable_type);
        $this->assertSame($user->id, $log->auditable_id);
        $this->assertSame(['old_field' => 'old_value'], $log->old_values);
        $this->assertSame(['new_field' => 'new_value'], $log->new_values);
    }

    /** @test */
    public function audit_log_morphable_relation_resolves_to_user(): void
    {
        $user = User::factory()->freelancer()->approved()->create();

        $this->actingAs($user);

        $log = AuditLog::log('test.event', 'description', 'system', $user);

        $this->assertInstanceOf(User::class, $log->auditable);
        $this->assertSame($user->id, $log->auditable->id);
    }

    /** @test */
    public function audit_log_area_icon_and_color_return_correct_values(): void
    {
        $log        = new AuditLog(['area' => 'auth']);
        $this->assertSame('icon-auth', $log->areaIcon());
        $this->assertSame('blue', $log->areaColor());

        $bookingLog = new AuditLog(['area' => 'bookings']);
        $this->assertSame('icon-bookings', $bookingLog->areaIcon());
        $this->assertSame('green', $bookingLog->areaColor());
    }
}
