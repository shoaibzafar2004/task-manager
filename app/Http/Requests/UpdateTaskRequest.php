<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'info' => ['nullable', 'string', 'max:'.StoreTaskRequest::INFO_MAX_LENGTH],
            'due_date' => ['nullable', 'date'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'labels' => ['nullable', 'array'],
            'labels.*' => ['integer', 'distinct', 'exists:labels,id'],
            'priority' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
