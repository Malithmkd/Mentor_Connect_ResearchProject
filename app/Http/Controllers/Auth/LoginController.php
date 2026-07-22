<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * LoginController
 * Handles user authentication with rate limiting.
 * Redirects to role-specific dashboard after login.
 * Blocks accounts that are pending admin approval or have been rejected.
 */
class LoginController extends Controller
{
    /**
     * Show login form.
     */
    public function show(): View
    {
        return view('auth.login');
    }

    /**
     * Handle login with rate limiting and approval gate.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Attempt authentication (credentials check + rate limiting)
        $request->authenticate();

        $user = Auth::user();

        // --- Admin-approval gate ---
        // Non-admin users must be approved before they can access the system.
        if (!$user->isAdmin()) {
            if ($user->account_status === 'pending') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Redirect back to the login page with a visible notification
                return redirect()->route('login')
                    ->with('approval_pending', true)
                    ->withInput($request->only('email'));
            }

            if ($user->account_status === 'rejected') {
                $reason = $user->rejection_reason;
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Redirect back to the login page with rejection details
                return redirect()->route('login')
                    ->with('approval_rejected', true)
                    ->with('rejection_reason', $reason)
                    ->withInput($request->only('email'));
            }
        }

        $request->session()->regenerate();

        AuditLog::log(
            'auth.login',
            "{$user->full_name} ({$user->role->label()}) logged in",
            'auth',
            $user,
        );

        return $this->redirectBasedOnRole();
    }

    /**
     * Log out user and invalidate session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $name = $user?->full_name ?? 'Unknown';
        $role = $user?->role?->label() ?? 'Unknown';

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        AuditLog::create([
            'event'      => 'auth.logout',
            'description'=> "{$name} ({$role}) logged out",
            'area'       => 'auth',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('home');
    }

    /**
     * Redirect authenticated user to their role-specific dashboard.
     */
    private function redirectBasedOnRole(): RedirectResponse
    {
        $user = auth()->user();

        return match (true) {
            $user->isAdmin()      => redirect()->route('admin.dashboard'),
            $user->isMentor()     => redirect()->route('mentor.dashboard'),
            $user->isFreelancer() => redirect()->route('freelancer.dashboard'),
            default               => redirect()->route('home'),
        };
    }
}
