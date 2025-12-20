<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateTenantSlugRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->route('id');

        $reservedSlugs = [
            'api',
            'admin',
            'auth',
            'setup',
            'session',
            'dev',
        ];

        return [
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('tenants', 'slug')->ignore($tenantId),
                Rule::unique('tenant_slug_histories', 'slug'),
                Rule::notIn($reservedSlugs),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.required' => __('Portal slug is required.'),
            'slug.regex' => __('Portal slug format is invalid. Use lowercase letters, numbers, and hyphens only.'),
            'slug.unique' => __('Portal slug is already registered. Please use a different slug.'),
            'slug.not_in' => __('Portal slug is not allowed. Please use a different slug.'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->input('slug') === null
                ? null
                : Str::of((string) $this->input('slug'))->trim()->lower()->toString(),
        ]);
    }
}
