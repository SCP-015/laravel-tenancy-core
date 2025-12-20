<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
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
            'name' => 'required|min:3|max:255',
            'slug' => 'nullable|string|max:255',
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
            'name.required' => __('Portal name is required.'),
            'name.min' => __('Portal name must be at least :min characters.', ['min' => 3]),
            'name.max' => __('Portal name must not exceed :max characters.', ['max' => 255]),
            'slug.string' => __('Portal slug must be a valid text.'),
            'slug.max' => __('Portal slug must not exceed :max characters.', ['max' => 255]),
        ];
    }
}
