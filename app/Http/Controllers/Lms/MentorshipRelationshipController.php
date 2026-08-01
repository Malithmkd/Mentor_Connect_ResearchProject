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
 *  - Freelancer can renew with a new duration
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
     * Enforces: one LMS module per freelancer-mentor pair.
     */
    public function requestLongTerm(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'booking_id'      => ['required', 'exists:bookings,id'],
            'payment_type'    => ['required', 'in:hourly,monthly,per_module,custom'],
            'payment_amount'  => ['nullable', 'numeric', 'min:0'],
            'payment_notes'   => ['nullable', 'string', 'max:1000'],
            'duration_months' => ['nullable', 'integer', 'in:1,3,6,12'],
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);

        // Guard: must be the freelancer on this booking
        abort_if(auth()->id() !== $booking->freelancer_id, 403);

        // Guard: booking must be completed or reviewed
        abort_if(
            !in_array($booking->status, [BookingStatus::COMPLETED, BookingStatus::REVIEWED], true),
            422,
            'Session must be completed first.'
        );

        // Guard: no existing relationship for this booking
        if ($booking->mentorshipRelationship()->exists()) {
            return back()->with('error', 'A long-term request already exists for this session.');
        }

        // Guard: one LMS module per freelancer-mentor pair
        $alreadyExists = MentorshipRelationship::where('freelancer_id', $booking->freelancer_id)
            ->where('mentor_id', $booking->mentor_id)
            ->whereIn('status', [RelationshipStatus::PENDING->value, RelationshipStatus::ACCEPTED->value])
            ->exists();

        if ($alreadyExists) {
            return back()->with('error', 'You already have an active or pending mentorship with this mentor. Use the renewal option to extend it.');
        }

        $durationMonths = $validated['duration_months'] ?? null;
        $expiresAt = $durationMonths ? now()->addMonths($durationMonths) : null;

        MentorshipRelationship::create([
            'booking_id'      => $booking->id,
            'mentor_id'       => $booking->mentor_id,
            'freelancer_id'   => $booking->freelancer_id,
            'status'          => RelationshipStatus::PENDING,
            'payment_type'    => $validated['payment_type'],
            'payment_amount'  => $validated['payment_amount'] ?? null,
            'payment_notes'   => $validated['payment_notes'] ?? null,
            'requested_at'    => now(),
            'duration_months' => $durationMonths,
            'expires_at'      => $expiresAt,
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

        $expiresAt = $relationship->duration_months
            ? now()->addMonths($relationship->duration_months)
            : null;

        $relationship->update([
            'status'      => RelationshipStatus::ACCEPTED,
            'accepted_at' => now(),
            'expires_at'  => $expiresAt,
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

    /**
     * Freelancer renews an accepted mentorship with a new duration.
     */
    public function renew(Request $request, MentorshipRelationship $relationship): RedirectResponse
    {
        abort_if(auth()->id() !== $relationship->freelancer_id, 403);
        abort_if(!$relationship->isAccepted(), 422, 'Only active mentorships can be renewed.');

        $validated = $request->validate([
            'duration_months' => ['required', 'integer', 'in:1,3,6,12'],
        ]);

        // Extend from today or from existing future expiry
        $base = ($relationship->expires_at && $relationship->expires_at->isFuture())
            ? $relationship->expires_at
            : now();

        $relationship->update([
            'duration_months' => $validated['duration_months'],
            'expires_at'      => $base->addMonths($validated['duration_months']),
        ]);

        return back()->with('success', 'Mentorship renewed! Duration extended by ' . $validated['duration_months'] . ' month(s).');
    }
}
