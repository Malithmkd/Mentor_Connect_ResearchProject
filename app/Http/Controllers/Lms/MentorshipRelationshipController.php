<?php

namespace App\Http\Controllers\Lms;

use App\Enums\BookingStatus;
use App\Enums\RelationshipStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\MentorshipRelationship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MentorshipRelationshipController
 * Handles the full lifecycle of a long-term mentorship request:
 *  - Freelancer sends request after a completed booking
 *  - Mentor accepts or declines the request
 *  - Both roles can list their relationships
 */
class MentorshipRelationshipController extends Controller
{
    /**
     * List all relationships for the authenticated user (mentor or freelancer).
     */
    public function index(): View
    {
        $user = auth()->user();

        if ($user->isMentor()) {
            $relationships = MentorshipRelationship::with(['freelancer', 'booking', 'courses'])
                ->forMentor($user->id)
                ->latest()
                ->paginate(15);
        } else {
            $relationships = MentorshipRelationship::with(['mentor', 'booking', 'courses'])
                ->forFreelancer($user->id)
                ->latest()
                ->paginate(15);
        }

        return view('mentor.lms.relationships.index', compact('relationships'));
    }

    /**
     * Freelancer sends a "continue long-term" request after a completed session.
     */
    public function requestLongTerm(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'booking_id'     => ['required', 'exists:bookings,id'],
            'payment_type'   => ['required', 'in:hourly,monthly,per_module,custom'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_notes'  => ['nullable', 'string', 'max:1000'],
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);

        // Guard: must be the freelancer on this booking
        abort_if(auth()->id() !== $booking->freelancer_id, 403);

        // Guard: booking must be completed or reviewed (reviewed = completed + review submitted)
        abort_if(
            !in_array($booking->status, [BookingStatus::COMPLETED, BookingStatus::REVIEWED], true),
            422,
            'Session must be completed first.'
        );

        // Guard: no existing relationship for this booking
        if ($booking->mentorshipRelationship()->exists()) {
            return back()->with('error', 'A long-term request already exists for this session.');
        }

        MentorshipRelationship::create([
            'booking_id'     => $booking->id,
            'mentor_id'      => $booking->mentor_id,
            'freelancer_id'  => $booking->freelancer_id,
            'status'         => RelationshipStatus::PENDING,
            'payment_type'   => $validated['payment_type'],
            'payment_amount' => $validated['payment_amount'] ?? null,
            'payment_notes'  => $validated['payment_notes'] ?? null,
            'requested_at'   => now(),
        ]);

        return back()->with('success', 'Long-term mentorship request sent! The mentor will review it shortly.');
    }

    /**
     * Mentor accepts a pending long-term request.
     */
    public function accept(MentorshipRelationship $relationship): RedirectResponse
    {
        abort_if(auth()->id() !== $relationship->mentor_id, 403);
        abort_if(!$relationship->isPending(), 422, 'This request is no longer pending.');

        $relationship->update([
            'status'      => RelationshipStatus::ACCEPTED,
            'accepted_at' => now(),
        ]);

        return redirect()
            ->route('mentor.lms.courses.index', $relationship)
            ->with('success', 'Long-term relationship accepted! You can now create a course for this freelancer.');
    }

    /**
     * Mentor declines a pending long-term request.
     */
    public function decline(MentorshipRelationship $relationship): RedirectResponse
    {
        abort_if(auth()->id() !== $relationship->mentor_id, 403);
        abort_if(!$relationship->isPending(), 422, 'This request is no longer pending.');

        $relationship->update(['status' => RelationshipStatus::DECLINED]);

        return redirect()
            ->route('mentor.lms.relationships.index')
            ->with('info', 'Long-term request declined.');
    }
}
