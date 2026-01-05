<?php

namespace App\Http\Requests\Tenant\DigitalSignature;

use Illuminate\Foundation\Http\FormRequest;

class VerifyDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:pdf|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File dokumen wajib diunggah.',
            'file.mimes' => 'File harus berformat PDF.',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ];
    }
}
