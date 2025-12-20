<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class SubmitPublicFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url'           => 'required|string',
            'category'      => 'required|string|in:Saran,Pujian,Keluhan',
            'feedback'      => 'required|string',
            'sender_name'   => 'required|string|max:255',
            'sender_email'  => 'required|email|max:255',
            'screenshots'   => 'array|max:4',
            'screenshots.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048', 
        ];
    }
    
    public function messages(): array
    {
        return [
            'url.required'                  => 'URL halaman tidak terdeteksi.',
            'url.string'                    => 'URL halaman harus berupa teks yang valid.',
            'category.required'             => 'Kategori masukan wajib dipilih (Saran, Pujian, atau Keluhan).',
            'category.string'               => 'Kategori masukan harus berupa teks yang valid.',
            'category.in'                   => 'Kategori masukan harus salah satu dari: Saran, Pujian, atau Keluhan.',
            'feedback.required'             => 'Kolom masukan wajib diisi.',
            'feedback.string'               => 'Kolom masukan harus berupa teks yang valid.',
            'sender_name.required'          => 'Nama pengirim wajib diisi.',
            'sender_name.string'            => 'Nama pengirim harus berupa teks yang valid.',
            'sender_name.max'               => 'Nama pengirim maksimal 255 karakter.',
            'sender_email.required'         => 'Email pengirim wajib diisi.',
            'sender_email.string'           => 'Email pengirim harus berupa teks yang valid.',
            'sender_email.email'            => 'Format email tidak valid.',
            'sender_email.max'              => 'Email pengirim maksimal 255 karakter.',
            'screenshots.array'             => 'Screenshots harus berupa daftar file.',
            'screenshots.max'               => 'Maksimal 4 gambar dapat diunggah.',
            'screenshots.*.image'           => 'File harus berupa gambar.',
            'screenshots.*.mimes'           => 'Format gambar yang diizinkan: JPG, JPEG, PNG, GIF.',
            'screenshots.*.max'             => 'Ukuran gambar maksimal adalah 2MB.',
        ];
    }
}
