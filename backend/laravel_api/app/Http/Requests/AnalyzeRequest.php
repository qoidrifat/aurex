<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnalyzeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image_id' => 'required|exists:images,id',
        ];
    }

    public function messages(): array
    {
        return [
            'image_id.required' => 'ID gambar wajib diisi.',
            'image_id.exists' => 'Gambar tidak ditemukan.',
        ];
    }
}
