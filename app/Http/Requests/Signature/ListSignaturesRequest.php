<?php

namespace App\Http\Requests\Signature;

use Illuminate\Foundation\Http\FormRequest;

class ListSignaturesRequest extends FormRequest
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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
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
            'user_id.required' => 'User ID is required.',
            'user_id.uuid' => 'User ID must be a valid UUID.',
            'user_id.exists' => 'User not found.',
            'per_page.integer' => 'Per page must be an integer.',
            'per_page.min' => 'Per page must be at least 1.',
            'per_page.max' => 'Per page must not exceed 100.',
            'search.max' => 'Search query must not exceed 255 characters.',
        ];
    }
}
