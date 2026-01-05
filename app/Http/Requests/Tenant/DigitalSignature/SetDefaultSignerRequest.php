<?php

namespace App\Http\Requests\Tenant\DigitalSignature;

use Illuminate\Foundation\Http\FormRequest;

class SetDefaultSignerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_ids.required' => 'Minimal harus ada 1 penandatangan default.',
            'user_ids.*.exists' => 'User tidak ditemukan.',
        ];
    }
}
