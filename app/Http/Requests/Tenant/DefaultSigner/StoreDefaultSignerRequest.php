<?php

namespace App\Http\Requests\Tenant\DefaultSigner;

use Illuminate\Foundation\Http\FormRequest;

class StoreDefaultSignerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'workgroup_id' => 'required|exists:workgroups,id',
            'user_id' => 'required|exists:users,id',
            'step_order' => 'required|integer|min:1',
            'role' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'workgroup_id.required' => 'Workgroup harus dipilih.',
            'workgroup_id.exists' => 'Workgroup tidak ditemukan.',
            'user_id.required' => 'User harus dipilih.',
            'user_id.exists' => 'User tidak ditemukan.',
            'step_order.required' => 'Urutan signing harus diisi.',
            'step_order.min' => 'Urutan signing minimal 1.',
        ];
    }
}
