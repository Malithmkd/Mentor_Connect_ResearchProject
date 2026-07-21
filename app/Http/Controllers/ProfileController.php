<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\MentorProfile;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * ProfileController
 * Handles profile viewing and updating for all roles.
 */
class ProfileController extends Controller
{
    /**
     * Show profile for the authenticated user.
     */
    public function show(): View
    {
        $user = auth()->user();

        return view('profile.show', [
            'user' => $user->load('mentorProfile'),
        ]);
    }

    /**
     * Show any user's public profile (viewable by authenticated users).
     */
    public function view(User $user): View
    {
        $user->load(['mentorProfile', 'gigs' => fn ($q) => $q->published()]);

        $receivedReviews = Review::byReviewee($user->id)
            ->with(['reviewer', 'gig'])
            ->where('is_public', true)
            ->recent()
            ->get();

        $averageRating = $receivedReviews->avg('rating') ?? 0;
        $totalReviews  = $receivedReviews->count();

        return view('profile.view', [
            'user'          => $user,
            'receivedReviews' => $receivedReviews,
            'averageRating' => $averageRating,
            'totalReviews'  => $totalReviews,
        ]);
    }

    /**
     * Show profile edit form.
     */
    public function edit(): View
    {
        $user = auth()->user();

        return view('profile.edit', [
            'user' => $user->load('mentorProfile'),
        ]);
    }

    /**
     * Update user profile.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = auth()->user();

        // Update base user fields
        $userFields = array_intersect_key($data, array_flip([
            'first_name', 'last_name', 'email', 'bio', 'location', 'timezone',
        ]));

        if (!empty($userFields)) {
            $user->update($userFields);
        }

        // Update mentor profile fields
        if ($user->isMentor() && $user->mentorProfile) {
            $mentorFields = array_intersect_key($data, array_flip([
                'headline', 'about', 'company', 'website',
                'linkedin_url', 'github_url', 'years_experience', 'hourly_rate',
            ]));

            if (!empty($mentorFields)) {
                $user->mentorProfile->update($mentorFields);
            }
        }

        return redirect()
            ->route('profile.show')
            ->with('success', 'Profile updated successfully!');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('profile.edit')
            ->with('password_success', 'Password changed successfully!');
    }

    /**
     * Upload / replace profile avatar.
     */
    public function uploadAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048', 'mimes:jpeg,png,gif,webp'],
        ]);

        $user = auth()->user();

        // Delete old avatar file if it exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Store new avatar in public/avatars/{user_id}/
        $path = $request->file('avatar')->store("avatars/{$user->id}", 'public');

        $user->update(['avatar' => $path]);

        return redirect()
            ->route('profile.edit')
            ->with('avatar_success', 'Profile picture updated!');
    }
}
