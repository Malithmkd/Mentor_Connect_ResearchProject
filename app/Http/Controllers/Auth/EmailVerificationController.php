<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * EmailVerificationController
 * Handles email verification flow.
 * Required before freelancers can book sessions.
 */
class EmailVerificationController extends Controller
{
    /**
     * Show email verification notice.
     */
    public function notice(): View
    {
        return view('auth.verify-email');
    }

    /**
     * Handle email verification link.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->route('freelancer.dashboard')
            ->with('success', 'Email verified! You can now book mentoring sessions.');
    }

    /**
     * Resend verification email.
     */
    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('freelancer.dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Verification link sent!');
    }
}
