<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\MentorProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * RegisterController
 * Handles user registration with role selection.
 * Creates MentorProfile automatically for mentor registrations.
 * Does NOT auto-login — account is set to 'pending' until admin approves.
 */
class RegisterController extends Controller
{
    /**
     * Show registration form.
     */
    public function show(): View
    {
        return view('auth.register');
    }

    /**
     * Handle registration request.
     * Account is created in 'pending' state — admin must approve before user can log in.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'first_name'     => $data['first_name'],
            'last_name'      => $data['last_name'],
            'email'          => $data['email'],
            'password'       => Hash::make($data['password']),
            'role'           => UserRole::from($data['role']),
            'bio'            => $data['bio'] ?? null,
            'location'       => $data['location'] ?? null,
            'timezone'       => $data['timezone'] ?? 'UTC',
            'account_status' => 'pending',   // awaits admin approval
        ]);

        // Create mentor profile for mentor registrations
        if ($user->isMentor()) {
            MentorProfile::create([
                'user_id'             => $user->id,
                'verification_status' => 'pending',
                'headline'            => $data['headline'] ?? null,
                'about'               => $data['about'] ?? null,
                'company'             => $data['company'] ?? null,
                'website'             => $data['website'] ?? null,
                'linkedin_url'        => $data['linkedin_url'] ?? null,
                'github_url'          => $data['github_url'] ?? null,
                'years_experience'    => $data['years_experience'] ?? 0,
                'hourly_rate'         => $data['hourly_rate'] ?? null,
            ]);
        }

        // Do NOT log in or send email verification — admin approval required first.
        return redirect()->route('approval.pending')
            ->with('success', 'Your application has been submitted and is awaiting admin review.');
    }
}
