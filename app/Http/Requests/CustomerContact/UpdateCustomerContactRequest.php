<?php

namespace App\Http\Requests\CustomerContact;

use App\Http\Requests\CustomerContact\StoreCustomerContactRequest;

class UpdateCustomerContactRequest extends StoreCustomerContactRequest
{
    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:30', 'regex:/^[0-9+\s().-]+$/'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'consultation_content' => ['sometimes', 'required', 'string', 'max:5000'],
        ];
    }
}
