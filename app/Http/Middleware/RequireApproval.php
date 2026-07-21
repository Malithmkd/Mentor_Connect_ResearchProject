<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * RequireApproval Middleware
 * Checks that an authenticated user's account has been approved by an admin.
 * Pending or rejected accounts are logged out and redirected to an info page.
 */
class RequireApproval
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Admins are never subject to the approval gate
        if ($user->isAdmin()) {
            return $next($request);
        }

        if ($user->account_status === 'pending') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('approval.pending')
                ->with('info', 'Your account is awaiting admin approval.');
        }

        if ($user->account_status === 'rejected') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('approval.rejected')
                ->with('reason', $user->rejection_reason);
        }

        return $next($request);
    }
}
