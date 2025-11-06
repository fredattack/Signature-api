<?php

namespace App\Http\Requests\Wallpaper;

use Illuminate\Foundation\Http\FormRequest;

class ListWallpapersRequest extends FormRequest
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
            'signature_id' => ['nullable', 'uuid', 'exists:signatures,id'],
            'template_id' => ['nullable', 'uuid', 'exists:wallpaper_templates,id'],
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
            'signature_id.uuid' => 'Signature ID must be a valid UUID.',
            'signature_id.exists' => 'Signature not found.',
            'template_id.uuid' => 'Template ID must be a valid UUID.',
            'template_id.exists' => 'Template not found.',
            'page.integer' => 'Page must be a valid integer.',
            'page.min' => 'Page must be at least 1.',
            'per_page.integer' => 'Items per page must be a valid integer.',
            'per_page.min' => 'Items per page must be at least 1.',
            'per_page.max' => 'Items per page must not exceed 100.',
        ];
    }
}
