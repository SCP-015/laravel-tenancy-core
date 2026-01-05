<?php

namespace App\Http\Requests\Tenant\DigitalSignature;

use Illuminate\Foundation\Http\FormRequest;

class SignDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agreement' => 'required|accepted',
            'certificate_id' => 'required|exists:user_certificates,id',
        ];
    }

    public function messages(): array
    {
        return [
            'agreement.required' => 'Anda harus menyetujui untuk menandatangani dokumen.',
            'agreement.accepted' => 'Anda harus menyetujui untuk menandatangani dokumen.',
            'certificate_id.required' => 'Certificate ID wajib diisi.',
            'certificate_id.exists' => 'Sertifikat tidak ditemukan.',
        ];
    }
}
