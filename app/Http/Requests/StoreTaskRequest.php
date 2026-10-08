<?php

namespace App\Http\Requests;

use App\Enums\Recurrence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    /**
     * Longest task details allowed, in characters.
     */
    public const INFO_MAX_LENGTH = 20000;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'info' => ['nullable', 'string', 'max:'.StoreTaskRequest::INFO_MAX_LENGTH],
            'due_date' => ['nullable', 'date'],
            'recurrence' => ['nullable', Rule::enum(Recurrence::class)],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'labels' => ['nullable', 'array'],
            'labels.*' => ['integer', 'distinct', 'exists:labels,id'],
            'priority' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
