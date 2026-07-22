<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => 'sometimes|string|max:255',
            'phone' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('users', 'phone')->ignore($userId)
            ],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'birthday' => 'sometimes|date|before:today',
            'address' => 'sometimes|string|max:256',
            'avatar' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
            'password' => 'sometimes|string|min:6|confirmed',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.string' => __('validation.custom.name.string'),
            'name.max' => __('validation.custom.name.max'),
            'phone.string' => __('validation.custom.phone.string'),
            'phone.max' => __('validation.custom.phone.max'),
            'phone.unique' => __('validation.custom.phone.unique'),
            'email.email' => __('validation.custom.email.email'),
            'email.max' => __('validation.custom.email.max'),
            'email.unique' => __('validation.custom.email.unique'),
            'birthday.date' => __('validation.custom.birthday.date'),
            'birthday.before' => __('validation.custom.birthday.before'),
            'address.string' => __('validation.custom.address.string'),
            'address.max' => __('validation.custom.address.max'),
            'password.min' => __('validation.custom.password.min'),
            'password.confirmed' => __('validation.custom.password.confirmed'),
        ];
    }
}
