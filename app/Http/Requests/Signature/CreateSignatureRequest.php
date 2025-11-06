<?php

namespace App\Http\Requests\Signature;

use Illuminate\Foundation\Http\FormRequest;

class CreateSignatureRequest extends FormRequest
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
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:10240'], // 10MB max
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
            'name.required' => 'Signature name is required.',
            'name.max' => 'Signature name must not exceed 255 characters.',
            'description.max' => 'Description must not exceed 1000 characters.',
            'image.required' => 'Signature image is required.',
            'image.image' => 'File must be an image.',
            'image.mimes' => 'Image must be a file of type: jpeg, png, jpg, gif.',
            'image.max' => 'Image must not be larger than 10MB.',
        ];
    }
}
