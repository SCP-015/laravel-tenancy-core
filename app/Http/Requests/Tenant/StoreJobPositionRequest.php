<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobPositionRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('job_positions', 'name')->whereNull('deleted_at'),
            ],
            'id_parent' => 'nullable|exists:job_positions,id',
            'nusawork_id' => 'nullable|integer',
            'nusawork_name' => 'nullable|string|max:255',
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
            'name.required' => __('Job position name is required.'),
            'name.string' => __('Job position name must be a valid text.'),
            'name.max' => __('Job position name must not exceed :max characters.', ['max' => 255]),
            'name.unique' => __('Job position name is already registered. Please use a different name.'),
            'id_parent.exists' => __('Selected parent job position does not exist.'),
            'nusawork_id.integer' => __('Nusawork ID must be a valid number.'),
            'nusawork_name.string' => __('Nusawork name must be a valid text.'),
            'nusawork_name.max' => __('Nusawork name must not exceed :max characters.', ['max' => 255]),
        ];
    }
}
