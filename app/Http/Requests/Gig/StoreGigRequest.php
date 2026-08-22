<?php

namespace App\Http\Requests\Gig;

use App\Enums\GigStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Gig creation/update validation.
 * Only mentors can create gigs. Status defaults to draft.
 */
class StoreGigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isMentor();
    }

    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:5000'],
            'what_to_expect' => ['nullable', 'string', 'max:2000'],
            'prerequisites' => ['nullable', 'string', 'max:2000'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'delivery_format' => ['required', 'string', 'in:video_call,voice_call,chat,async'],
            'experience_level' => ['required', 'string', 'in:beginner,intermediate,advanced,all_levels'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'status' => ['required', 'string', new Enum(GigStatus::class)],
            'max_sessions_per_week' => ['required', 'integer', 'min:1', 'max:50'],
            'booking_lead_time_hours' => ['required', 'integer', 'min:0', 'max:168'],
            'skills' => ['required', 'array', 'min:1', 'max:10'],
            'skills.*' => ['required', 'integer', 'exists:skills,id'],
        ];

        // On update, slug stays the same
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['title'][] = 'sometimes';
            $rules['description'][] = 'sometimes';
            $rules['skills'][] = 'sometimes';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Please provide a title for your mentoring session.',
            'description.required' => 'Please describe what you will cover in this session.',
            'price.min' => 'Price must be at least $0.',
            'price.max' => 'Price cannot exceed $9,999.99.',
            'duration_minutes.min' => 'Session must be at least 15 minutes.',
            'duration_minutes.max' => 'Session cannot exceed 8 hours.',
            'skills.required' => 'Select at least one skill this session covers.',
            'skills.max' => 'You can select up to 10 skills.',
        ];
    }

    public function attributes(): array
    {
        return [
            'what_to_expect' => 'what to expect',
            'delivery_format' => 'delivery format',
            'experience_level' => 'experience level',
            'duration_minutes' => 'session duration',
            'max_sessions_per_week' => 'maximum sessions per week',
            'booking_lead_time_hours' => 'booking lead time',
        ];
    }
}
