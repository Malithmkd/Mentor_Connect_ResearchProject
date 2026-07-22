<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin Registration Approval Controller
 * Manages the queue of users awaiting account approval.
 * Admin can approve, reject (with optional reason), or view full details.
 */
class RegistrationApprovalController extends Controller
{
    /**
     * List all pending registration requests.
     */
    public function index(Request $request): View
    {
        $query = User::where('account_status', 'pending')
            ->with('mentorProfile')
            ->latest();

        // Filter by role
        if ($request->filled('role')) {
            $role = UserRole::tryFrom($request->input('role'));
            if ($role) {
                $query->byRole($role);
            }
        }

        // Search by name
        if ($search = $request->input('search')) {
            $query->searchByName($search);
        }

        $pending = $query->paginate(20)->withQueryString();

        // Also fetch recently processed (last 10) for reference
        $recentlyProcessed = User::whereIn('account_status', ['approved', 'rejected'])
            ->where('role', '!=', UserRole::ADMIN->value)
            ->latest('updated_at')
            ->take(10)
            ->get();

        return view('admin.approvals.index', [
            'pending'           => $pending,
            'recentlyProcessed' => $recentlyProcessed,
            'filters'           => $request->only(['role', 'search']),
            'totalPending'      => User::where('account_status', 'pending')->count(),
        ]);
    }

    /**
     * Approve a registration request — user can now log in.
     */
    public function approve(User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 403);

        $user->update([
            'account_status'   => 'approved',
            'rejection_reason' => null,
        ]);

        AuditLog::log(
            'user.approved',
            "Approved registration for {$user->full_name} ({$user->role->label()})",
            'approvals',
            $user,
            ['account_status' => 'pending'],
            ['account_status' => 'approved'],
        );

        return back()->with('success', "{$user->full_name} has been approved and can now log in.");
    }

    /**
     * Reject a registration request with an optional reason.
     */
    public function reject(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 403);

        $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user->update([
            'account_status'   => 'rejected',
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        AuditLog::log(
            'user.rejected',
            "Rejected registration for {$user->full_name} ({$user->role->label()})",
            'approvals',
            $user,
            ['account_status' => 'pending'],
            ['account_status' => 'rejected', 'rejection_reason' => $request->input('rejection_reason')],
        );

        return back()->with('success', "{$user->full_name}'s registration has been rejected.");
    }

    /**
     * Re-open a rejected account back to pending (allows admin to reconsider).
     */
    public function reopen(User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 403);

        $user->update([
            'account_status'   => 'pending',
            'rejection_reason' => null,
        ]);

        AuditLog::log(
            'user.reopen',
            "Re-opened application for {$user->full_name} ({$user->role->label()})",
            'approvals',
            $user,
            ['account_status' => 'rejected'],
            ['account_status' => 'pending'],
        );

        return back()->with('success', "{$user->full_name}'s application has been moved back to pending.");
    }
}
