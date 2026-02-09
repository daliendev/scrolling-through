<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_post_id' => ['nullable', 'integer', 'exists:posts,id'],
            'posts_read' => ['required', 'integer', 'min:0'],
        ];
    }
}
