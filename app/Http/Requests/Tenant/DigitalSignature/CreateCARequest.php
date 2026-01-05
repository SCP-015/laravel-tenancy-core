<?php

namespace App\Http\Requests\Tenant\DigitalSignature;

use Illuminate\Foundation\Http\FormRequest;

class CreateCARequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'common_name' => 'required|string|max:255',
            'organization' => 'required|string|max:255',
            'country' => 'required|string|size:2',
            'state' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'validity_days' => 'nullable|integer|min:365|max:7300',
        ];
    }

    public function messages(): array
    {
        return [
            'common_name.required' => 'Common Name wajib diisi.',
            'organization.required' => 'Organisasi wajib diisi.',
            'country.required' => 'Kode negara wajib diisi.',
            'country.size' => 'Kode negara harus 2 karakter (contoh: ID).',
        ];
    }
}
