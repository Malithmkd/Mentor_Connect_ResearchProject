<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates mentor's note reply on a booking.
 * Only the mentor of the booking is allowed to submit a reply note.
 */
class MentorNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $booking = $this->route('booking');
        return auth()->check()
            && auth()->user()->isMentor()
            && $booking
            && $booking->mentor_id === auth()->id();
    }

    public function rules(): array
    {
        return [
            'mentor_note' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'mentor_note.required' => 'Please enter a reply note.',
            'mentor_note.max'      => 'The reply note may not exceed 1000 characters.',
        ];
    }
}
