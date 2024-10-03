<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['string'],
            'due_date' => ['date'],
            'assignee' => ['exists:users,id'],
            'body' => ['string'],
            'status_id' => ['exists:statuses,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
