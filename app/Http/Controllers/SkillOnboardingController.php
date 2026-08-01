<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SkillOnboardingController
 * Shown to newly registered users on their first login.
 * Lets them select their preferred skills, then marks them as onboarded.
 */
class SkillOnboardingController extends Controller
{
    /**
     * Show the skill selection onboarding page.
     */
    public function show(): View
    {
        // If already onboarded, redirect to their dashboard
        $user = auth()->user();
        if ($user->skills_onboarded) {
            return redirect()->to($this->dashboardRoute($user));
        }

        $skills = Skill::active()->orderBy('category')->orderBy('name')->get()
            ->groupBy('category');

        return view('onboarding.skills', compact('skills'));
    }

    /**
     * Save selected skills and mark user as onboarded.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'skills'   => ['nullable', 'array'],
            'skills.*' => ['integer', 'exists:skills,id'],
        ]);

        $user = auth()->user();

        // Sync selected skills
        $user->skills()->sync($request->input('skills', []));

        // Mark as onboarded
        $user->update(['skills_onboarded' => true]);

        return redirect()->to($this->dashboardRoute($user))
            ->with('success', 'Welcome! Your skill preferences have been saved. We\'ve personalized your mentor feed for you.');
    }

    private function dashboardRoute($user): string
    {
        if ($user->isAdmin()) {
            return route('admin.dashboard');
        }
        if ($user->isMentor()) {
            return route('mentor.dashboard');
        }
        return route('freelancer.dashboard');
    }
}
