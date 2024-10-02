<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'workspace_id' => ['required', 'exists:workspaces'],
            'title' => ['required'],
            'created_by' => ['required', 'exists:users'],
            'due_date' => ['required', 'date'],
            'assignee' => ['required', 'exists:users'],
            'body' => ['required'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
