<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadSelfieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'File gambar wajib diupload.',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Format gambar harus JPEG atau PNG.',
            'image.max' => 'Ukuran gambar maksimal 5MB.',
        ];
    }
}
