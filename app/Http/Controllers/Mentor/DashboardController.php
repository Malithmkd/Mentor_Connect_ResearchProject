<?php

namespace App\Http\Controllers\Mentor;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Gig;
use App\Models\Review;
use Illuminate\View\View;

/**
 * Mentor Dashboard Controller
 * Shows earnings, pending requests, gig performance, and recent reviews.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();
        $profile = auth()->user()->mentorProfile;

        // Earnings & stats
        $totalEarnings = Booking::byMentor($userId)
            ->whereIn('status', [BookingStatus::COMPLETED, BookingStatus::REVIEWED])
            ->sum('price_paid');

        $pendingRequests = Booking::byMentor($userId)
            ->pendingResponse()
            ->count();

        $activeGigs = Gig::byMentor($userId)
            ->whereIn('status', ['published', 'paused'])
            ->count();

        $totalSessions = Booking::byMentor($userId)
            ->whereIn('status', [BookingStatus::COMPLETED, BookingStatus::REVIEWED])
            ->count();

        // Recent pending requests
        $recentRequests = Booking::byMentor($userId)
            ->pendingResponse()
            ->with(['freelancer', 'gig'])
            ->recent()
            ->take(5)
            ->get();

        // Upcoming scheduled sessions
        $upcomingSessions = Booking::byMentor($userId)
            ->where('status', BookingStatus::SCHEDULED)
            ->with(['freelancer', 'gig'])
            ->orderBy('scheduled_at')
            ->take(5)
            ->get();

        $pendingReviews = Booking::byMentor($userId)
            ->awaitingReview($userId)
            ->count();

        // Recent reviews
        $recentReviews = Review::byMentor($userId)
            ->with(['freelancer', 'gig'])
            ->recent()
            ->take(5)
            ->get();

        // Gig performance
        $gigPerformance = Gig::byMentor($userId)
            ->withCount(['bookings', 'reviews'])
            ->orderBy('total_bookings', 'desc')
            ->take(5)
            ->get();

        return view('mentor.dashboard', [
            'profile' => $profile,
            'stats' => [
                'earnings' => $totalEarnings,
                'pending_requests' => $pendingRequests,
                'active_gigs' => $activeGigs,
                'total_sessions' => $totalSessions,
                'average_rating' => $profile?->average_rating ?? 0,
                'pending_reviews' => $pendingReviews,
            ],
            'recentRequests' => $recentRequests,
            'upcomingSessions' => $upcomingSessions,
            'recentReviews' => $recentReviews,
            'gigPerformance' => $gigPerformance,
        ]);
    }
}
