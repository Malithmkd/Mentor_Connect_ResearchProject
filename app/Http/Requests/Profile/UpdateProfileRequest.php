<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Profile update validation.
 * Email uniqueness check excludes the current user.
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $userId = auth()->id();

        $rules = [
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId),
            ],
            'bio' => ['nullable', 'string', 'max:1000'],
            'location' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:50'],
        ];

        // Mentor-specific fields
        if (auth()->user()->isMentor()) {
            $rules['headline'] = ['nullable', 'string', 'max:200'];
            $rules['about'] = ['nullable', 'string', 'max:5000'];
            $rules['company'] = ['nullable', 'string', 'max:100'];
            $rules['website'] = ['nullable', 'url', 'max:255'];
            $rules['linkedin_url'] = ['nullable', 'url', 'max:255'];
            $rules['github_url'] = ['nullable', 'url', 'max:255'];
            $rules['years_experience'] = ['nullable', 'integer', 'min:0', 'max:60'];
            $rules['hourly_rate'] = ['nullable', 'numeric', 'min:0', 'max:9999.99'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already in use by another account.',
            'website.url' => 'Please provide a valid website URL.',
            'linkedin_url.url' => 'Please provide a valid LinkedIn URL.',
        ];
    }
}
