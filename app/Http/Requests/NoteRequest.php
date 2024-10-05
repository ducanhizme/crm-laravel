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
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
