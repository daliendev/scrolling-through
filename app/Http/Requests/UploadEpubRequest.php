<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadEpubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        \Log::info('🔍 VALIDATION REQUEST', [
            'has_file_epub' => $this->hasFile('epub'),
            'all_files' => array_keys($this->allFiles()),
            'all_input' => array_keys($this->all()),
            'content_type' => $this->header('Content-Type'),
        ]);

        return [
            'epub' => ['required', 'file', 'mimes:epub,application/epub+zip', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'epub.required' => 'Please upload an EPUB file.',
            'epub.mimes' => 'The file must be an EPUB format.',
            'epub.max' => 'The EPUB file must not exceed 10MB.',
        ];
    }
}
