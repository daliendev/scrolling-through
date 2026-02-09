<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'Please enter a note.',
            'text.max' => 'The note must not exceed 1000 characters.',
        ];
    }
}
