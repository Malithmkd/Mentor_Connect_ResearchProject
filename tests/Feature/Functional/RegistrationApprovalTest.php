<?php

namespace Tests\Feature\Functional;

use App\Models\AuditLog;
use App\Models\MentorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Registration & Admin Approval Pipeline Tests.
 *
 * Covers:
 * - Registration defaults to pending status
 * - Three login gates: pending, rejected, inactive
 * - Admin approve/reject workflow
 * - MentorProfile auto-creation
 * - AuditLog entries on registration and approval
 */
class RegistrationApprovalTest extends TestCase
{
    use RefreshDatabase;

    // ─── Registration ─────────────────────────────────────────────────────────

    /** @test */
    public function registration_creates_pending_account_and_does_not_log_in(): void
    {
        $response = $this->post('/register', [
            'first_name'            => 'Jane',
            'last_name'             => 'Doe',
            'email'                 => 'jane@example.com',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role'                  => 'freelancer',
            'terms'                 => true,
        ]);

        $response->assertRedirect(route('approval.pending'));

        $this->assertDatabaseHas('users', [
            'email'          => 'jane@example.com',
            'account_status' => 'pending',
            'role'           => 'freelancer',
        ]);

        // Must NOT be authenticated
        $this->assertGuest();
    }

    /** @test */
    public function registration_creates_mentor_profile_for_mentor_role(): void
    {
        $this->post('/register', [
            'first_name'            => 'Bob',
            'last_name'             => 'Smith',
            'email'                 => 'mentor@example.com',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role'                  => 'mentor',
            'headline'              => 'Senior Engineer',
            'years_experience'      => 5,
            'terms'                 => true,
        ]);

        $user = User::where('email', 'mentor@example.com')->first();

        $this->assertNotNull($user);
        $this->assertDatabaseHas('mentor_profiles', [
            'user_id'             => $user->id,
            'verification_status' => 'pending',
        ]);
    }

    /** @test */
    public function registration_logs_audit_event(): void
    {
        $this->post('/register', [
            'first_name'            => 'Alice',
            'last_name'             => 'Test',
            'email'                 => 'alice@example.com',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role'                  => 'freelancer', 
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'user.registered',
            'area'  => 'auth',
        ]);
    }

    // ─── Login Gate 1: Pending ────────────────────────────────────────────────

    /** @test */
    public function pending_user_cannot_login_and_is_redirected_with_flash(): void
    {
        $user = User::factory()->freelancer()->pending()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('approval_pending', true);
        $this->assertGuest();
    }

    // ─── Login Gate 2: Rejected ───────────────────────────────────────────────

    /** @test */
    public function rejected_user_cannot_login_and_sees_rejection_reason(): void
    {
        $user = User::factory()->freelancer()->rejected('Invalid credentials provided.')->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('approval_rejected', true);
        $response->assertSessionHas('rejection_reason', 'Invalid credentials provided.');
        $this->assertGuest();
    }

    // ─── Login Gate 3: Inactive ───────────────────────────────────────────────

    /** @test */
    public function inactive_user_cannot_login_regardless_of_account_status(): void
    {
        $user = User::factory()->freelancer()->approved()->inactive()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('disabled_account', true);
        $this->assertGuest();
    }

    /** @test */
    public function admin_user_with_inactive_flag_is_also_blocked(): void
    {
        $admin = User::factory()->admin()->inactive()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email'    => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('disabled_account', true);
    }

    // ─── Admin Approval ───────────────────────────────────────────────────────

    /** @test */
    public function admin_can_approve_pending_user(): void
    {
        $admin       = User::factory()->admin()->create();
        $pendingUser = User::factory()->freelancer()->pending()->create();

        $this->actingAs($admin)
             ->patch(route('admin.approvals.approve', $pendingUser));

        $this->assertDatabaseHas('users', [
            'id'             => $pendingUser->id,
            'account_status' => 'approved',
        ]);
    }

    /** @test */
    public function admin_can_reject_user_with_reason(): void
    {
        $admin       = User::factory()->admin()->create();
        $pendingUser = User::factory()->freelancer()->pending()->create();

        $this->actingAs($admin)
             ->patch(route('admin.approvals.reject', $pendingUser), [
                 'rejection_reason' => 'Incomplete profile information.',
             ]);

        $this->assertDatabaseHas('users', [
            'id'               => $pendingUser->id,
            'account_status'   => 'rejected',
            'rejection_reason' => 'Incomplete profile information.',
        ]);
    }

    /** @test */
    public function approved_freelancer_can_login_and_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->freelancer()->approved()->create([
            'password'         => bcrypt('password'),
            'skills_onboarded' => true,
        ]);

        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('freelancer.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function approved_mentor_can_login_and_is_redirected_to_mentor_dashboard(): void
    {
        $user = User::factory()->mentor()->approved()->create([
            'password'         => bcrypt('password'),
            'skills_onboarded' => true,
        ]);

        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('mentor.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function login_logs_auth_event_for_approved_user(): void
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
    public function unonboarded_user_is_redirected_to_skill_onboarding_after_login(): void
    {
        $user = User::factory()->freelancer()->approved()->unonboarded()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('onboarding.skills.show'));
    }

    /** @test */
    public function non_admin_cannot_access_admin_approval_routes(): void
    {
        $freelancer  = User::factory()->freelancer()->approved()->create();
        $pendingUser = User::factory()->freelancer()->pending()->create();

        $this->actingAs($freelancer)
             ->patch(route('admin.approvals.approve', $pendingUser))
             ->assertForbidden();
    }
}
