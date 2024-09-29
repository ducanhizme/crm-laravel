<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkspaceRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            return [
                'name' => ['required','string'],
            ];
        }
        return [
            'name' => ['string'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
