<?php

namespace App\Http\Controllers\Freelancer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Gig;
use App\Models\Review;
use Illuminate\View\View;

/**
 * Freelancer Dashboard Controller
 * Personalized dashboard showing bookings, stats, and recommendations.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();
        $user   = auth()->user();

        // Booking stats
        $totalBookings = Booking::byFreelancer($userId)->count();
        $activeBookings = Booking::byFreelancer($userId)
            ->whereIn('status', ['requested', 'accepted', 'scheduled'])
            ->count();
        $completedSessions = Booking::byFreelancer($userId)
            ->whereIn('status', ['completed', 'reviewed'])
            ->count();
        $pendingReviews = Booking::byFreelancer($userId)
            ->awaitingReview($userId)
            ->count();

        // Recent bookings
        $recentBookings = Booking::byFreelancer($userId)
            ->with(['mentor', 'gig'])
            ->recent()
            ->take(5)
            ->get();

        // Recent reviews received by the freelancer
        $recentReviews = Review::byReviewee($userId)
            ->with(['reviewer', 'gig'])
            ->recent()
            ->take(5)
            ->get();

        // Recommended gigs based on past bookings
        $bookedSkillIds = Booking::byFreelancer($userId)
            ->with('gig.skills')
            ->get()
            ->pluck('gig.skills.*.id')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        $recommendedGigs = !empty($bookedSkillIds)
            ? Gig::published()
                ->whereNotIn('id', Booking::byFreelancer($userId)->pluck('gig_id'))
                ->bySkills($bookedSkillIds)
                ->with('mentor')
                ->take(4)
                ->get()
            : Gig::published()
                ->with('mentor')
                ->orderByPopularity()
                ->take(4)
                ->get();

        return view('freelancer.dashboard', [
            'stats' => [
                'total'           => $totalBookings,
                'active'          => $activeBookings,
                'completed'       => $completedSessions,
                'pending_reviews' => $pendingReviews,
                'average_rating'  => (float) ($user->average_rating ?? 0),
                'total_reviews'   => (int) ($user->total_reviews ?? 0),
            ],
            'recentBookings'  => $recentBookings,
            'recentReviews'   => $recentReviews,
            'recommendedGigs' => $recommendedGigs,
        ]);
    }
}
