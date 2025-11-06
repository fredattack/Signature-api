<?php

namespace App\Http\Requests\Wallpaper;

use Illuminate\Foundation\Http\FormRequest;

class GenerateWallpaperRequest extends FormRequest
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
            'signature_id' => ['required', 'uuid', 'exists:signatures,id'],
            'template_id' => ['required', 'uuid', 'exists:wallpaper_templates,id'],
            'resolution' => ['nullable', 'string', 'regex:/^\d+x\d+$/'],
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
            'signature_id.required' => 'Signature ID is required.',
            'signature_id.uuid' => 'Signature ID must be a valid UUID.',
            'signature_id.exists' => 'Signature not found.',
            'template_id.required' => 'Template ID is required.',
            'template_id.uuid' => 'Template ID must be a valid UUID.',
            'template_id.exists' => 'Template not found.',
            'resolution.regex' => 'Resolution must be in format: 1920x1080',
        ];
    }
}
