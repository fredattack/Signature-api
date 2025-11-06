<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class OauthAppleRequest extends FormRequest
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
            'identity_token' => ['required', 'string'],
            'email' => ['nullable', 'email'],
            'name' => ['nullable', 'string', 'max:255'],
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
            'identity_token.required' => 'Apple identity token is required.',
            'email.email' => 'Please provide a valid email address.',
            'name.max' => 'Name must not exceed 255 characters.',
        ];
    }
}
