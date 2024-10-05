<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NoteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'workspace_id' => ['required', 'exists:workspaces'],
            'title' => ['nullable'],
            'body' => ['nullable'],
            'created_by' => ['required', 'exists:users'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
