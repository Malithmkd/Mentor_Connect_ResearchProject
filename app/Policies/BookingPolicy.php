<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;

/**
 * BookingPolicy
 * Resource-level authorization for booking operations.
 * Freelancers can manage their own bookings.
 * Mentors can respond to bookings directed to them.
 * Admins have full access.
 */
class BookingPolicy
{
    /**
     * Users can view their own bookings.
     * Mentors can view bookings where they are the mentor.
     * Admins can view all.
     */
    public function view(User $user, Booking $booking): bool
    {
        return $user->id === $booking->freelancer_id
            || $user->id === $booking->mentor_id
            || $user->isAdmin();
    }

    /**
     * Users can view their own bookings list.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Freelancers can create booking requests.
     * Must be verified and cannot book their own gig.
     */
    public function create(User $user): bool
    {
        return $user->isFreelancer();
    }

    /**
     * Freelancer can update their own draft/request.
     * Mentor can accept/reject (status update).
     * Neither can modify after completion.
     */
    public function update(User $user, Booking $booking): bool
    {
        if ($booking->status->isTerminal()) {
            return false;
        }

        // Freelancer can update their own draft
        if ($user->id === $booking->freelancer_id) {
            return in_array($booking->status, [BookingStatus::DRAFT, BookingStatus::REQUESTED], true);
        }

        // Mentor can respond to requests
        if ($user->id === $booking->mentor_id) {
            return $booking->status === BookingStatus::REQUESTED;
        }

        return $user->isAdmin();
    }

    /**
     * Either party can cancel if the booking is in a cancellable state.
     * Admin can always cancel.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        if (!$booking->status->canCancel()) {
            return false;
        }

        return $user->id === $booking->freelancer_id
            || $user->id === $booking->mentor_id
            || $user->isAdmin();
    }

    /**
     * Users who completed a session can leave a review.
     */
    public function review(User $user, Booking $booking): bool
    {
        return $booking->canBeReviewedBy($user);
    }

    /**
     * Only the mentor can mark as completed.
     */
    public function complete(User $user, Booking $booking): bool
    {
        return ($user->id === $booking->mentor_id || $user->isAdmin())
            && $booking->status === BookingStatus::SCHEDULED;
    }
}
