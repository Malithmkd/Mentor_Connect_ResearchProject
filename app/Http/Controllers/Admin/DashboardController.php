<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Gig;
use App\Models\Review;
use App\Models\User;
use Illuminate\View\View;

/**
 * Admin Dashboard Controller
 * Platform-wide statistics and management overview.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $totalUsers = User::count();
        $totalMentors = User::byRole(UserRole::MENTOR)->count();
        $totalFreelancers = User::byRole(UserRole::FREELANCER)->count();
        $pendingVerifications = User::byRole(UserRole::MENTOR)
            ->whereHas('mentorProfile', fn($q) => $q->where('verification_status', 'pending'))
            ->count();

        $totalGigs = Gig::count();
        $publishedGigs = Gig::where('status', 'published')->count();

        $totalBookings = Booking::count();
        $completedBookings = Booking::whereIn('status', [
            BookingStatus::COMPLETED,
            BookingStatus::REVIEWED,
        ])->count();
        $pendingBookings = Booking::where('status', BookingStatus::REQUESTED)->count();

        $totalRevenue = Booking::whereIn('status', [
            BookingStatus::COMPLETED,
            BookingStatus::REVIEWED,
        ])->sum('price_paid');

        $totalReviews = Review::count();
        $averageRating = Review::avg('rating') ?? 0;

        // Recent data
        $recentUsers = User::latest()->take(5)->get();
        $recentBookings = Booking::with(['freelancer', 'mentor', 'gig'])
            ->latest()
            ->take(5)
            ->get();
        $pendingMentors = User::byRole(UserRole::MENTOR)
            ->whereHas('mentorProfile', fn($q) => $q->where('verification_status', 'pending'))
            ->with('mentorProfile')
            ->take(5)
            ->get();

        return view('admin.dashboard', [
            'stats' => [
                'users'                => $totalUsers,
                'mentors'              => $totalMentors,
                'freelancers'          => $totalFreelancers,
                'pending_verifications' => $pendingVerifications,
                'pending_approvals'    => User::where('account_status', 'pending')->count(),
                'gigs'                 => $totalGigs,
                'published_gigs'       => $publishedGigs,
                'bookings'             => $totalBookings,
                'completed_bookings'   => $completedBookings,
                'pending_bookings'     => $pendingBookings,
                'revenue'              => $totalRevenue,
                'reviews'              => $totalReviews,
                'average_rating'       => round($averageRating, 1),
            ],
            'recentUsers' => $recentUsers,
            'recentBookings' => $recentBookings,
            'pendingMentors' => $pendingMentors,
        ]);
    }
}
