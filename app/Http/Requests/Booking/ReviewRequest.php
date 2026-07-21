<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Review submission validation.
 * Only freelancers who completed a session can leave reviews.
 */
class ReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isFreelancer() || auth()->user()->isMentor());
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'is_public' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Please provide a star rating.',
            'rating.min' => 'Rating must be at least 1 star.',
            'rating.max' => 'Rating cannot exceed 5 stars.',
            'comment.max' => 'Review comment cannot exceed 2000 characters.',
        ];
    }
}
