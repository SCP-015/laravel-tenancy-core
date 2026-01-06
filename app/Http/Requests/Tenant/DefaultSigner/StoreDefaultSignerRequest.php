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
            'workgroup_id' => 'nullable|exists:workgroups,id',
            'workgroup' => 'required_without:workgroup_id|string|max:100',
            'user_id' => 'required|exists:users,id',
            'step_order' => 'required|integer|min:1',
            'role' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'workgroup_id.exists' => 'Workgroup tidak ditemukan.',
            'workgroup.required_without' => 'Workgroup harus diisi.',
            'user_id.required' => 'User harus dipilih.',
            'user_id.exists' => 'User tidak ditemukan.',
            'step_order.required' => 'Urutan signing harus diisi.',
            'step_order.min' => 'Urutan signing minimal 1.',
        ];
    }

}
