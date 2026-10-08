<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewMarkdownRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'text' => ['nullable', 'string', 'max:'.StoreTaskRequest::INFO_MAX_LENGTH],
        ];
    }
}
