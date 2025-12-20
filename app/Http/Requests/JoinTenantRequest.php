<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinTenantRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => 'required|min:6|max:10',
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
            'code.required' => __('Portal code is required.'),
            'code.min' => __('Portal code must be at least :min characters.', ['min' => 6]),
            'code.max' => __('Portal code must not exceed :max characters.', ['max' => 10]),
        ];
    }
}
