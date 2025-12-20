<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobLevelRequest extends FormRequest
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
        $jobLevelId = $this->route('job_level');
        
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('job_levels', 'name')
                    ->ignore($jobLevelId)
                    ->whereNull('deleted_at'),
            ],
            'index' => 'nullable|integer',
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
            'name.string' => __('Job level name must be a valid text.'),
            'name.max' => __('Job level name must not exceed :max characters.', ['max' => 255]),
            'name.unique' => __('Job level name is already registered. Please use a different name.'),
            'index.integer' => __('Index must be a valid number.'),
            'nusawork_id.integer' => __('Nusawork ID must be a valid number.'),
            'nusawork_name.string' => __('Nusawork name must be a valid text.'),
            'nusawork_name.max' => __('Nusawork name must not exceed :max characters.', ['max' => 255]),
        ];
    }
}
