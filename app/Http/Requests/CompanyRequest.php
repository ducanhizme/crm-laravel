<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
{
    public function rules(): array
    {
       if ($this->isMethod('post')) {
           return [
               'name' => ['required', 'string'],
               'address' => ['string'],
               'domain_name' => ['url'],
               'employees' => ['integer'],
           ];
        } else {
            return [
                'name' => 'string|max:255',
                'address' => 'string|max:255',
                'domain_name' => 'string|max:255',
                'employees' => 'integer',
            ];
        }
    }

    public function authorize(): bool
    {
        return true;
    }
}
