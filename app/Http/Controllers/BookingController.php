<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Http\Requests\Booking\ReviewRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\UpdateStatusRequest;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Gig;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * BookingController
 * Handles the complete session booking lifecycle.
 * State machine: draft -> requested -> accepted -> scheduled -> completed -> reviewed
 */
class BookingController extends Controller
{
    /**
     * Store new booking request.
     * Transition: draft -> requested
     */
    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        /** @var Gig $gig */
        $gig = Gig::published()->findOrFail($data['gig_id']);
        $freelancer = auth()->user();

        // Prevent booking own gig
        if ($gig->mentor_id === $freelancer->id) {
            return back()->with('error', 'You cannot book your own session.');
        }

        $booking = Booking::create([
            'freelancer_id' => $freelancer->id,
            'mentor_id' => $gig->mentor_id,
            'gig_id' => $gig->id,
            'status' => BookingStatus::REQUESTED,
            'requested_at' => now(),
            'price_paid' => $gig->price,
            'freelancer_note' => $data['freelancer_note'] ?? null,
            'proposed_date' => !empty($data['proposed_date']) ? $data['proposed_date'] : null,
        ]);

        // Increment gig bookings count
        $gig->increment('total_bookings');

        AuditLog::log(
            'booking.created',
            "{$freelancer->full_name} requested a booking for '{$gig->title}'",
            'bookings',
            $booking,
            null,
            ['status' => BookingStatus::REQUESTED->value, 'gig_id' => $gig->id, 'price_paid' => $booking->price_paid],
        );

        // Notify mentor
        Notification::create([
            'user_id' => $gig->mentor_id,
            'type' => 'booking_requested',
            'title' => 'New Session Request',
            'message' => "{$freelancer->full_name} requested a session for '{$gig->title}'.",
            'action_url' => route('mentor.bookings.show', $booking),
            'action_text' => 'View Request',
            'related_booking_id' => $booking->id,
        ]);

        return redirect()
            ->route('freelancer.bookings.index')
            ->with('success', 'Session requested! The mentor will respond shortly.');
    }

    /**
     * Freelancer: view their bookings.
     */
    public function freelancerIndex(): View
    {
        $bookings = Booking::byFreelancer(auth()->id())
            ->with(['mentor', 'gig'])
            ->recent()
            ->paginate(10);

        $pendingReview = Booking::byFreelancer(auth()->id())
            ->awaitingReview(auth()->id())
            ->count();

        return view('freelancer.bookings.index', [
            'bookings' => $bookings,
            'pendingReview' => $pendingReview,
        ]);
    }

    /**
     * Mentor: view their incoming bookings.
     */
    public function mentorIndex(): View
    {
        $bookings = Booking::byMentor(auth()->id())
            ->with(['freelancer', 'gig'])
            ->recent()
            ->paginate(10);

        $pendingResponse = Booking::byMentor(auth()->id())
            ->pendingResponse()
            ->count();

        $pendingReview = Booking::byMentor(auth()->id())
            ->awaitingReview(auth()->id())
            ->count();

        return view('mentor.bookings.index', [
            'bookings' => $bookings,
            'pendingResponse' => $pendingResponse,
            'pendingReview' => $pendingReview,
        ]);
    }

    /**
     * Show booking detail.
     */
    public function show(Booking $booking): View
    {
        $this->authorize('view', $booking);

        $view = auth()->user()->isMentor()
            ? 'mentor.bookings.show'
            : 'freelancer.bookings.show';

        return view($view, [
            'booking' => $booking->load(['freelancer', 'mentor', 'gig', 'freelancerReview', 'mentorReview']),
        ]);
    }

    /**
     * Handle status transition (accept/reject/schedule/complete).
     * Uses state machine for validation.
     */
    public function updateStatus(UpdateStatusRequest $request, Booking $booking): RedirectResponse
    {
        $data = $request->validated();
        $newStatus = BookingStatus::from($data['status']);
        $note = $data['note'] ?? null;

        // Validate policy authorization
        match ($newStatus) {
            BookingStatus::ACCEPTED, BookingStatus::REJECTED =>
                $this->authorize('respond-to-booking', $booking),
            BookingStatus::SCHEDULED =>
                $this->authorize('respond-to-booking', $booking),
            BookingStatus::COMPLETED =>
                $this->authorize('complete', $booking),
            BookingStatus::CANCELLED =>
                $this->authorize('cancel', $booking),
            default => abort(403),
        };

        // Apply state machine transition
        if (!$booking->transitionTo($newStatus, $note)) {
            return back()->with('error', 'Invalid status transition.');
        }

        // Add meeting link when scheduling
        if ($newStatus === BookingStatus::SCHEDULED && !empty($data['meeting_link'])) {
            $booking->update([
                'meeting_link' => $data['meeting_link'],
                'meeting_provider' => $this->detectMeetingProvider($data['meeting_link']),
            ]);
        }

        // Notify other party
        $this->sendStatusNotification($booking, $newStatus);

        $message = match ($newStatus) {
            BookingStatus::ACCEPTED => 'Session request accepted!',
            BookingStatus::REJECTED => 'Session request declined.',
            BookingStatus::SCHEDULED => 'Session scheduled successfully!',
            BookingStatus::COMPLETED => 'Session marked as completed.',
            BookingStatus::CANCELLED => 'Session cancelled.',
            default => 'Status updated.',
        };

        return redirect()->back()->with('success', $message);
    }

    /**
     * Cancel a booking.
     */
    public function cancel(Booking $booking): RedirectResponse
    {
        $this->authorize('cancel', $booking);

        $booking->transitionTo(BookingStatus::CANCELLED, 'Cancelled by ' . auth()->user()->full_name);

        // Notify other party
        $otherPartyId = auth()->id() === $booking->freelancer_id
            ? $booking->mentor_id
            : $booking->freelancer_id;

        Notification::create([
            'user_id' => $otherPartyId,
            'type' => 'booking_cancelled',
            'title' => 'Session Cancelled',
            'message' => 'A session has been cancelled by ' . auth()->user()->full_name,
            'related_booking_id' => $booking->id,
        ]);

        return redirect()->back()->with('success', 'Session cancelled.');
    }

    /**
     * Submit review for completed session.
     */
    public function review(ReviewRequest $request, Booking $booking): RedirectResponse
    {
        $this->authorize('review', $booking);

        $data = $request->validated();
        $reviewerId = auth()->id();
        $revieweeId = ($reviewerId === $booking->freelancer_id)
            ? $booking->mentor_id
            : $booking->freelancer_id;

        $review = $booking->reviews()->create([
            'reviewer_id' => $reviewerId,
            'reviewee_id' => $revieweeId,
            'freelancer_id' => $booking->freelancer_id,
            'mentor_id' => $booking->mentor_id,
            'gig_id' => $booking->gig_id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'is_public' => $data['is_public'] ?? true,
        ]);

        // Transition booking to reviewed if currently completed
        if ($booking->status->value === 'completed') {
            $booking->transitionTo(BookingStatus::REVIEWED);
        }

        // Update reviewee average rating
        if ($revieweeId === $booking->mentor_id) {
            // Reviewee is mentor: update gig & mentor profile
            $gig = $booking->gig;
            $newGigRating = $gig->reviews()->where(function ($q) use ($revieweeId) {
                $q->where('reviewee_id', $revieweeId)
                  ->orWhereNull('reviewee_id');
            })->avg('rating');
            $gig->update(['average_rating' => round($newGigRating, 1)]);

            $mentorProfile = $booking->mentor->mentorProfile;
            if ($mentorProfile) {
                $newMentorRating = $booking->mentor->receivedReviews()->avg('rating');
                $totalReviews = $booking->mentor->receivedReviews()->count();
                $mentorProfile->update([
                    'average_rating' => round($newMentorRating, 1),
                    'total_reviews' => $totalReviews,
                ]);
            }
            $booking->mentor->update([
                'average_rating' => round($booking->mentor->receivedReviews()->avg('rating'), 1),
                'total_reviews' => $booking->mentor->receivedReviews()->count(),
            ]);
        } else {
            // Reviewee is freelancer: update User model rating
            $freelancer = $booking->freelancer;
            $newRating = $freelancer->receivedReviews()->avg('rating');
            $totalReviews = $freelancer->receivedReviews()->count();
            $freelancer->update([
                'average_rating' => round($newRating, 1),
                'total_reviews' => $totalReviews,
            ]);
        }

        // Notify reviewee
        Notification::create([
            'user_id' => $revieweeId,
            'type' => 'review_received',
            'title' => 'New Review Received',
            'message' => "You received a {$data['rating']}-star review from " . auth()->user()->full_name,
            'action_url' => route(auth()->user()->isMentor() ? 'freelancer.bookings.show' : 'mentor.bookings.show', $booking),
            'action_text' => 'View Review',
            'related_booking_id' => $booking->id,
        ]);

        $redirectRoute = auth()->user()->isMentor() ? 'mentor.bookings.index' : 'freelancer.bookings.index';

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Review submitted! Thank you for your feedback.');
    }

    /**
     * Send notification to the other party about status change.
     */
    private function sendStatusNotification(Booking $booking, BookingStatus $status): void
    {
        $isFreelancerAction = auth()->id() === $booking->freelancer_id;
        $recipientId = $isFreelancerAction ? $booking->mentor_id : $booking->freelancer_id;

        $notificationData = match ($status) {
            BookingStatus::ACCEPTED => [
                'type' => 'booking_accepted',
                'title' => 'Session Request Accepted',
                'message' => "Your session request for '{$booking->gig->title}' was accepted!",
                'action_text' => 'View Details',
            ],
            BookingStatus::REJECTED => [
                'type' => 'booking_rejected',
                'title' => 'Session Request Declined',
                'message' => "Your session request for '{$booking->gig->title}' was declined.",
                'action_text' => 'Find Another Mentor',
            ],
            BookingStatus::SCHEDULED => [
                'type' => 'booking_scheduled',
                'title' => 'Session Scheduled',
                'message' => "Your session '{$booking->gig->title}' has been scheduled.",
                'action_text' => 'View Details',
            ],
            BookingStatus::COMPLETED => [
                'type' => 'booking_completed',
                'title' => 'Session Completed',
                'message' => "Your session '{$booking->gig->title}' is complete. Leave a review!",
                'action_text' => 'Leave Review',
            ],
            BookingStatus::CANCELLED => [
                'type' => 'booking_cancelled',
                'title' => 'Session Cancelled',
                'message' => "Your session '{$booking->gig->title}' has been cancelled.",
                'action_text' => 'View Details',
            ],
            default => null,
        };

        if ($notificationData) {
            Notification::create([
                'user_id' => $recipientId,
                'type' => $notificationData['type'],
                'title' => $notificationData['title'],
                'message' => $notificationData['message'],
                'action_url' => route(auth()->user()->isFreelancer() ? 'freelancer.bookings.show' : 'mentor.bookings.show', $booking),
                'action_text' => $notificationData['action_text'],
                'related_booking_id' => $booking->id,
            ]);
        }
    }

    /**
     * Auto-detect meeting provider from link.
     */
    private function detectMeetingProvider(string $link): string
    {
        return match (true) {
            str_contains($link, 'zoom.us') => 'zoom',
            str_contains($link, 'meet.google') => 'google_meet',
            str_contains($link, 'teams.microsoft') => 'teams',
            default => 'other',
        };
    }
}
