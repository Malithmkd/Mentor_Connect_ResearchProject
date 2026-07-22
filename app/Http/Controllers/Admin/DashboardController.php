<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Gig;
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

        $auditToday = AuditLog::whereDate('created_at', today())->count();

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

        // Chart data: User Growth over past 7 days
        $userGrowthDates = collect(range(6, 0))->map(fn($days) => now()->subDays($days)->format('M d'));
        $userGrowthCounts = collect(range(6, 0))->map(fn($days) => User::whereDate('created_at', now()->subDays($days))->count());

        // Chart data: Booking Status Breakdown
        $bookingStats = [
            'Requested' => Booking::where('status', BookingStatus::REQUESTED)->count(),
            'Active' => Booking::whereIn('status', [BookingStatus::ACCEPTED, BookingStatus::SCHEDULED])->count(),
            'Completed' => Booking::whereIn('status', [BookingStatus::COMPLETED, BookingStatus::REVIEWED])->count(),
            'Cancelled' => Booking::whereIn('status', [BookingStatus::REJECTED, BookingStatus::CANCELLED])->count(),
        ];

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
                'audit_today'          => $auditToday,
            ],
            'charts' => [
                'userGrowthDates'  => $userGrowthDates->values(),
                'userGrowthCounts' => $userGrowthCounts->values(),
                'bookingStats'     => $bookingStats,
                'userDistribution' => ['Mentors' => $totalMentors, 'Freelancers' => $totalFreelancers],
            ],
            'recentUsers' => $recentUsers,
            'recentBookings' => $recentBookings,
            'pendingMentors' => $pendingMentors,
        ]);
    }
}
