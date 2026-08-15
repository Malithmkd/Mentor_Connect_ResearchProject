<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Booking request validation.
 * Freelancers must be verified and cannot book their own gigs.
 */
class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isFreelancer();
    }

    public function rules(): array
    {
        return [
            'gig_id' => ['required', 'integer', 'exists:gigs,id'],
            'freelancer_note' => ['nullable', 'string', 'max:1000'],
            'proposed_date' => ['nullable', 'date', 'after:today'],
            'proposed_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'gig_id.exists' => 'The selected gig does not exist.',
            'proposed_date.after' => 'Proposed date must be in the future.',
            'proposed_time.date_format' => 'Proposed time must be a valid time (HH:MM).',
        ];
    }
}
