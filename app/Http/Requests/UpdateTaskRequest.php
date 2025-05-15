<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\TaskStatus;
use App\Enums\TaskPriority;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:150',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => [new Enum(TaskStatus::class)],
            'priority' => [new Enum(TaskPriority::class)],
        ];
    }
}
