<?php

namespace App\Http\Requests\Booking;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Booking status transition validation.
 * Validates that the requested transition is valid per the state machine.
 */
class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        /** @var \App\Models\Booking $booking */
        $booking = $this->route('booking');

        // Get valid next statuses from current state
        $validStatuses = $booking
            ? array_map(
                fn(BookingStatus $s) => $s->value,
                $booking->status->validTransitions()
            )
            : [];

        return [
            'status' => [
                'required',
                'string',
                Rule::in($validStatuses),
            ],
            'note' => ['nullable', 'string', 'max:1000'],
            'meeting_link' => [
                'nullable',
                'url',
                'max:500',
                'required_if:status,' . BookingStatus::SCHEDULED->value,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Please select a new status.',
            'status.in' => 'This status transition is not allowed.',
            'meeting_link.required_if' => 'A meeting link is required when scheduling.',
        ];
    }
}
