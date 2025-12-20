<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class NusaworkCallbackRequest extends FormRequest
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
            'token' => 'required|string',
            'email' => 'required|string|email',
            'first_name' => 'required|string',
            'last_name' => 'nullable|string',
            'photo' => 'nullable|string',
            'company' => 'required|array',
            'company.name' => 'required|string',
            'company.address' => 'required|string',
            'join_code' => 'nullable|string',
            'force_create_user' => 'nullable|bool',
            'use_session_flow' => 'nullable|bool',
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
            'token.required' => __('Authentication token is required.'),
            'token.string' => __('Authentication token must be a valid text.'),
            'email.required' => __('Email is required.'),
            'email.string' => __('Email must be a valid text.'),
            'email.email' => __('Email format is invalid.'),
            'first_name.required' => __('First name is required.'),
            'first_name.string' => __('First name must be a valid text.'),
            'last_name.string' => __('Last name must be a valid text.'),
            'photo.string' => __('Photo URL must be a valid text.'),
            'company.required' => __('Company information is required.'),
            'company.array' => __('Company information must be a valid data structure.'),
            'company.name.required' => __('Company name is required.'),
            'company.name.string' => __('Company name must be a valid text.'),
            'company.address.required' => __('Company address is required.'),
            'company.address.string' => __('Company address must be a valid text.'),
            'join_code.string' => __('Join code must be a valid text.'),
            'force_create_user.bool' => __('Force create user must be a boolean value.'),
            'use_session_flow.bool' => __('Use session flow must be a boolean value.'),
        ];
    }
}
