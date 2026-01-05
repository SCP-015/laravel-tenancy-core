<?php

namespace App\Http\Requests\Tenant\DigitalSignature;

use Illuminate\Foundation\Http\FormRequest;

class CreateSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:pdf|max:10240',
            'title' => 'required|string|max:255',
            'mode' => 'required|in:sequential,parallel,hybrid',
            'signers' => 'required|array|min:1',
            'signers.*.user_id' => 'required|exists:users,id',
            'signers.*.role' => 'required|string|max:100',
            'signers.*.step_order' => 'required|integer|min:1',
            'signers.*.is_required' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File dokumen wajib diunggah.',
            'file.mimes' => 'File harus berformat PDF.',
            'file.max' => 'Ukuran file maksimal 10MB.',
            'title.required' => 'Judul dokumen wajib diisi.',
            'mode.required' => 'Mode signing wajib dipilih.',
            'mode.in' => 'Mode signing harus sequential, parallel, atau hybrid.',
            'signers.required' => 'Minimal harus ada 1 penandatangan.',
            'signers.*.user_id.required' => 'User ID penandatangan wajib diisi.',
            'signers.*.user_id.exists' => 'User tidak ditemukan.',
            'signers.*.role.required' => 'Role penandatangan wajib diisi.',
            'signers.*.step_order.required' => 'Urutan step wajib diisi.',
        ];
    }
}
