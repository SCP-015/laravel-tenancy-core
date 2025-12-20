<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExperienceLevelRequest extends FormRequest
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
        $experienceLevelId = $this->route('experience_level');
        
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('experience_levels', 'name')
                    ->ignore($experienceLevelId)
                    ->whereNull('deleted_at'),
            ],
            'index' => 'nullable|integer',
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
            'name.string' => __('Experience level name must be a valid text.'),
            'name.max' => __('Experience level name must not exceed :max characters.', ['max' => 255]),
            'name.unique' => __('Experience level name is already registered. Please use a different name.'),
            'index.integer' => __('Index must be a valid number.'),
        ];
    }
}
