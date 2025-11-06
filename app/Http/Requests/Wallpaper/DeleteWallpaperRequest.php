<?php

namespace App\Http\Requests\Wallpaper;

use Illuminate\Foundation\Http\FormRequest;

class DeleteWallpaperRequest extends FormRequest
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
            'id' => ['required', 'uuid', 'exists:wallpapers,id'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id.required' => 'Wallpaper ID is required.',
            'id.uuid' => 'Wallpaper ID must be a valid UUID.',
            'id.exists' => 'Wallpaper not found.',
        ];
    }
}
