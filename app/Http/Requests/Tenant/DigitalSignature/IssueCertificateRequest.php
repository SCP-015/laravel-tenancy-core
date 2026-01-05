<?php

namespace App\Http\Requests\Tenant\DigitalSignature;

use Illuminate\Foundation\Http\FormRequest;

class IssueCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'Label sertifikat wajib diisi.',
            'user_id.exists' => 'User tidak ditemukan.',
        ];
    }
}
