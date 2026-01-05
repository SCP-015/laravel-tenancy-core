<?php

namespace App\Http\Requests\Tenant\DefaultSigner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDefaultSignerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'workgroup_id' => 'sometimes|required|exists:workgroups,id',
            'user_id' => 'sometimes|required|exists:users,id',
            'step_order' => 'sometimes|required|integer|min:1',
            'role' => 'nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'workgroup_id.exists' => 'Workgroup tidak ditemukan.',
            'user_id.exists' => 'User tidak ditemukan.',
            'step_order.min' => 'Urutan signing minimal 1.',
        ];
    }
}
