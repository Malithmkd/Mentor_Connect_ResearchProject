<?php

namespace App\Policies;

use App\Models\Gig;
use App\Models\User;

/**
 * GigPolicy
 * Resource-level authorization for gig operations.
 * Enforces that mentors can only manage their own gigs.
 */
class GigPolicy
{
    /**
     * Anyone can view published gigs.
     */
    public function view(?User $user, Gig $gig): bool
    {
        return $gig->status->isPublic();
    }

    /**
     * Authenticated users can view published gigs.
     * Mentors can view their own drafts.
     * Admins can view all.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Only verified mentors can create gigs.
     */
    public function create(User $user): bool
    {
        return $user->isMentor()
            && $user->mentorProfile?->verification_status === 'verified';
    }

    /**
     * Only the gig owner or admin can update.
     */
    public function update(User $user, Gig $gig): bool
    {
        return ($user->id === $gig->mentor_id) || $user->isAdmin();
    }

    /**
     * Only the gig owner or admin can delete.
     */
    public function delete(User $user, Gig $gig): bool
    {
        return ($user->id === $gig->mentor_id) || $user->isAdmin();
    }

    /**
     * Only the gig owner can change status (publish/pause).
     */
    public function changeStatus(User $user, Gig $gig): bool
    {
        return $user->id === $gig->mentor_id;
    }

    /**
     * Only the gig owner or admin can restore soft-deleted gigs.
     */
    public function restore(User $user, Gig $gig): bool
    {
        return ($user->id === $gig->mentor_id) || $user->isAdmin();
    }
}
