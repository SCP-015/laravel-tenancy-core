<?php

namespace App\Http\Requests\Tenant\DigitalSignature;

use Illuminate\Foundation\Http\FormRequest;

class ScanQRRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qr_data' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'qr_data.required' => 'Data QR wajib diisi.',
            'qr_data.string' => 'Data QR harus berupa string.',
        ];
    }
}
