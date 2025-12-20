<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCompanyCategoryRequest extends FormRequest
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
        // Get ID from route parameter (for update operation)
        $categoryId = $this->route('company_category');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Pengecekan nama unik, mengabaikan ID saat ini jika sedang update
                Rule::unique('company_categories', 'name')->ignore($categoryId),
            ],
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama kategori',
            'description' => 'deskripsi',
            'is_active' => 'status aktif',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => __(':attribute must not be empty.'),
            'name.string' => __(':attribute must be a valid text.'),
            'name.max' => __(':attribute must not exceed :max characters.', ['max' => 255]),
            'name.unique' => __(':attribute is already registered. Please use a different name.'),
            'description.string' => __(':attribute must be a valid text.'),
            'is_active.boolean' => __(':attribute must be true or false.'),
        ];
    }
}
