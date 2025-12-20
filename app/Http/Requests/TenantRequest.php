<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantRequest extends FormRequest
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
        $tenantId = $this->route('id'); // Dapatkan ID dari route, kalau ada

        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('tenants', 'name')->ignore($tenantId),
            ],
            'code' => [
                'required',
                'string',
                'min:6',
                'max:10',
                Rule::unique('tenants', 'code')->ignore($tenantId),
            ],
            'enable_slug_history_redirect' => [
                'nullable',
                'boolean',
            ],
            'theme_color' => [
                'nullable',
                'string',
                'size:7',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'header_image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg',
                'max:2048', // 2MB max
            ],
            'profile_image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg',
                'max:2048',
            ],
            'company_category_id' => [
                'nullable',
                'integer',
                'exists:company_categories,id',
            ],
            'company_values' => 'nullable|string',
            'employee_range_start' => 'nullable|integer|min:1',
            'employee_range_end' => 'nullable|integer|min:1|gte:employee_range_start',
            'linkedin' => 'nullable|string|url',
            'instagram' => 'nullable|string|url',
            'website' => 'nullable|string|url',
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
            'name.string' => __('Portal name must be a valid text.'),
            'name.min' => __('Portal name must be at least :min characters.', ['min' => 3]),
            'name.max' => __('Portal name must not exceed :max characters.', ['max' => 255]),
            'name.unique' => __('Portal name is already registered. Please use a different name.'),
            'code.required' => __('Portal code is required.'),
            'code.string' => __('Portal code must be a valid text.'),
            'code.min' => __('Portal code must be at least :min characters.', ['min' => 6]),
            'code.max' => __('Portal code must not exceed :max characters.', ['max' => 10]),
            'code.unique' => __('Portal code is already registered. Please use a different code.'),
            'theme_color.string' => __('Theme color must be a valid text.'),
            'theme_color.size' => __('Theme color must be exactly :size characters (e.g., #FFFFFF).', ['size' => 7]),
            'theme_color.regex' => __('Theme color format is invalid. Use hex color format (e.g., #FFFFFF).'),
            'header_image.image' => __('Header image must be an image file.'),
            'header_image.mimes' => __('Header image must be a file of type: jpeg, png, jpg.'),
            'header_image.max' => __('Header image size must not exceed :max KB.', ['max' => 2048]),
            'profile_image.image' => __('Profile image must be an image file.'),
            'profile_image.mimes' => __('Profile image must be a file of type: jpeg, png, jpg.'),
            'profile_image.max' => __('Profile image size must not exceed :max KB.', ['max' => 2048]),
            'company_category_id.integer' => __('Company category must be a valid number.'),
            'company_category_id.exists' => __('Selected company category does not exist.'),
            'company_values.string' => __('Company values must be a valid text.'),
            'employee_range_start.integer' => __('Employee range start must be a valid number.'),
            'employee_range_start.min' => __('Employee range start must be at least :min.', ['min' => 1]),
            'employee_range_end.integer' => __('Employee range end must be a valid number.'),
            'employee_range_end.min' => __('Employee range end must be at least :min.', ['min' => 1]),
            'employee_range_end.gte' => __('Employee range end must be greater than or equal to employee range start.'),
            'linkedin.url' => __('LinkedIn URL must be a valid URL.'),
            'instagram.url' => __('Instagram URL must be a valid URL.'),
            'website.url' => __('Website URL must be a valid URL.'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'linkedin' => $this->input('linkedin') === '' ? null : $this->input('linkedin'),
            'instagram' => $this->input('instagram') === '' ? null : $this->input('instagram'),
            'website' => $this->input('website') === '' ? null : $this->input('website'),
        ]);
    }
}
