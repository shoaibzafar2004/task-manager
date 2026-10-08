<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ToggleChecklistItemRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'index' => ['required', 'integer', 'min:0'],
            'checked' => ['required', 'boolean'],
            'version' => ['required', 'string'],
        ];
    }
}
