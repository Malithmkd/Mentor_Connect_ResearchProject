<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gig search and filter validation.
 * All parameters are optional for flexible filtering.
 */
class SearchGigsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public search
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'skills' => ['nullable', 'array', 'max:10'],
            'skills.*' => ['integer', 'exists:skills,id'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
            'experience_level' => ['nullable', 'string', 'in:beginner,intermediate,advanced,all_levels'],
            'min_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'sort' => ['nullable', 'string', 'in:rating,price_asc,price_desc,popularity,newest'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'max_price.gte' => 'Maximum price must be greater than or equal to minimum price.',
            'sort.in' => 'Invalid sort option selected.',
        ];
    }
}
