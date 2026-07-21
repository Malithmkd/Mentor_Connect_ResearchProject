<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin User Management Controller
 * Allows administrators to browse, inspect, disable, and remove
 * mentor and freelancer accounts based on quality metrics (ratings).
 */
class UserManagementController extends Controller
{
    /* ─── List ─── */

    /**
     * Show all mentors with their rating data and filtering options.
     */
    public function mentors(Request $request): View
    {
        $query = User::byRole(UserRole::MENTOR)
            ->with(['mentorProfile', 'receivedReviews'])
            ->withCount('receivedReviews as review_count')
            ->withAvg('receivedReviews as avg_rating', 'rating');

        // Search
        if ($search = $request->input('search')) {
            $query->searchByName($search);
        }

        // Rating filter
        if ($request->filled('max_rating')) {
            $max = (float) $request->input('max_rating');
            $query->having('avg_rating', '<=', $max)->orHavingNull('avg_rating');
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        // Sort
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'rating_asc'  => $query->orderBy('avg_rating', 'asc'),
            'rating_desc' => $query->orderBy('avg_rating', 'desc'),
            'name'        => $query->orderBy('first_name'),
            default       => $query->latest(),
        };

        $users = $query->paginate(15)->withQueryString();

        return view('admin.users.index', [
            'users'    => $users,
            'role'     => 'mentor',
            'title'    => 'Manage Mentors',
            'filters'  => $request->only(['search', 'max_rating', 'status', 'sort']),
        ]);
    }

    /**
     * Show all freelancers with their rating data and filtering options.
     */
    public function freelancers(Request $request): View
    {
        $query = User::byRole(UserRole::FREELANCER)
            ->with(['receivedReviews'])
            ->withCount('receivedReviews as review_count')
            ->withAvg('receivedReviews as avg_rating', 'rating');

        // Search
        if ($search = $request->input('search')) {
            $query->searchByName($search);
        }

        // Rating filter
        if ($request->filled('max_rating')) {
            $max = (float) $request->input('max_rating');
            $query->having('avg_rating', '<=', $max)->orHavingNull('avg_rating');
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        // Sort
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'rating_asc'  => $query->orderBy('avg_rating', 'asc'),
            'rating_desc' => $query->orderBy('avg_rating', 'desc'),
            'name'        => $query->orderBy('first_name'),
            default       => $query->latest(),
        };

        $users = $query->paginate(15)->withQueryString();

        return view('admin.users.index', [
            'users'    => $users,
            'role'     => 'freelancer',
            'title'    => 'Manage Freelancers',
            'filters'  => $request->only(['search', 'max_rating', 'status', 'sort']),
        ]);
    }

    /* ─── Detail ─── */

    /**
     * Show full profile and review history of a single user.
     */
    public function show(User $user): View
    {
        $this->authorizeManagedUser($user);

        $user->load(['mentorProfile', 'gigs', 'bookingsAsMentor', 'bookingsAsFreelancer']);

        // All reviews received by this user
        $reviews = Review::where('reviewee_id', $user->id)
            ->orWhere(function ($q) use ($user) {
                // Legacy rows that store mentor_id / freelancer_id directly
                if ($user->isMentor()) {
                    $q->where('mentor_id', $user->id)->whereNull('reviewee_id');
                } else {
                    $q->where('freelancer_id', $user->id)->whereNull('reviewee_id');
                }
            })
            ->with(['reviewer', 'gig'])
            ->latest()
            ->get();

        // Rating breakdown (1–5 stars)
        $ratingBreakdown = [];
        for ($i = 5; $i >= 1; $i--) {
            $ratingBreakdown[$i] = $reviews->where('rating', $i)->count();
        }

        $avgRating    = $reviews->count() ? round($reviews->avg('rating'), 1) : null;
        $totalReviews = $reviews->count();
        $totalBookings = $user->isMentor()
            ? $user->bookingsAsMentor->count()
            : $user->bookingsAsFreelancer->count();

        return view('admin.users.show', compact(
            'user',
            'reviews',
            'ratingBreakdown',
            'avgRating',
            'totalReviews',
            'totalBookings',
        ));
    }

    /* ─── Actions ─── */

    /**
     * Toggle account active/disabled status.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        $this->authorizeManagedUser($user);

        $user->update(['is_active' => ! $user->is_active]);

        $action = $user->is_active ? 'enabled' : 'disabled';

        return back()->with('success', "{$user->full_name}'s account has been {$action}.");
    }

    /**
     * Permanently delete (hard-delete) a user account.
     * Only allowed for admin — irreversible.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeManagedUser($user);

        $role = $user->role->value;
        $name = $user->full_name;

        // Hard-delete (bypass SoftDeletes for full removal)
        $user->forceDelete();

        $redirectRoute = $role === 'mentor' ? 'admin.users.mentors' : 'admin.users.freelancers';

        return redirect()->route($redirectRoute)
            ->with('success', "{$name}'s account has been permanently removed.");
    }

    /* ─── Helpers ─── */

    /**
     * Prevent managing admin accounts through this controller.
     */
    private function authorizeManagedUser(User $user): void
    {
        abort_if($user->isAdmin(), 403, 'Cannot manage admin accounts.');
    }
}
