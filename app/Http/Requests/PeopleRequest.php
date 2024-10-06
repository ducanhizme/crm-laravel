<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PeopleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_id' => ['exists:companies'],
            'email' => [ 'email', 'max:254'],
            'job_title' => ['string'],
            'phones' => ['string'],
            'name'=>['string']
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
