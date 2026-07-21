<?php

namespace App\Http\Controllers;

use App\Models\Gig;
use App\Models\User;
use Illuminate\View\View;

/**
 * HomeController
 * Landing page with featured mentors and popular gigs.
 */
class HomeController extends Controller
{
    public function index(): View
    {
        $featuredGigs = Gig::published()
            ->with(['mentor', 'skills'])
            ->orderByRating('desc')
            ->take(6)
            ->get();

        $popularGigs = Gig::published()
            ->with(['mentor', 'skills'])
            ->orderByPopularity()
            ->take(4)
            ->get();

        $topMentors = User::verifiedMentors()
            ->with('mentorProfile')
            ->take(4)
            ->get();

        $stats = [
            'mentors' => User::byRole(\App\Enums\UserRole::MENTOR)->active()->count(),
            'sessions' => \App\Models\Booking::count(),
            'skills' => \App\Models\Skill::active()->count(),
        ];

        return view('home', [
            'featuredGigs' => $featuredGigs,
            'popularGigs' => $popularGigs,
            'topMentors' => $topMentors,
            'stats' => $stats,
        ]);
    }
}
