<?php

namespace App\Http\Requests\Feedback;

use Illuminate\Foundation\Http\FormRequest;

class ListFeedbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', 'in:bug,feature,general'],
            'status' => ['nullable', 'string', 'in:pending,reviewed,resolved'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Type must be bug, feature, or general.',
            'status.in' => 'Status must be pending, reviewed, or resolved.',
            'page.integer' => 'Page must be a valid integer.',
            'page.min' => 'Page must be at least 1.',
            'per_page.integer' => 'Items per page must be a valid integer.',
            'per_page.min' => 'Items per page must be at least 1.',
            'per_page.max' => 'Items per page must not exceed 100.',
        ];
    }
}
