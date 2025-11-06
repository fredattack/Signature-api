<?php

namespace App\Http\Requests\Feedback;

use Illuminate\Foundation\Http\FormRequest;

class SubmitFeedbackRequest extends FormRequest
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
            'type' => ['required', 'string', 'in:bug,feature,general'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
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
            'type.required' => 'Feedback type is required.',
            'type.in' => 'Feedback type must be bug, feature, or general.',
            'subject.required' => 'Subject is required.',
            'subject.max' => 'Subject must not exceed 200 characters.',
            'message.required' => 'Message is required.',
            'message.max' => 'Message must not exceed 5000 characters.',
        ];
    }
}
