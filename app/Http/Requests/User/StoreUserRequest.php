<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Response;

class StoreUserRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:50|unique:users,phone',
            'password' => 'required|string|min:6',
            'birthday' => 'nullable|date',
            'address' => 'nullable|string|max:256',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status' => 'nullable|integer',
            'is_super_admin' => 'nullable|boolean',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('validation.custom.user.name.required'),
            'name.string' => __('validation.custom.user.name.string'),
            'name.max' => __('validation.custom.user.name.max'),
            'email.required' => __('validation.custom.user.email.required'),
            'email.email' => __('validation.custom.user.email.email'),
            'email.unique' => __('validation.custom.user.email.unique'),
            'phone.required' => __('validation.custom.user.phone.required'),
            'phone.unique' => __('validation.custom.user.phone.unique'),
            'phone.max' => __('validation.custom.user.phone.max'),
            'password.required' => __('validation.custom.user.password.required'),
            'password.min' => __('validation.custom.user.password.min'),
            'birthday.date' => __('validation.custom.user.birthday.date'),
            'address.max' => __('validation.custom.user.address.max'),
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => __('validation.custom.user.invalid_data'),
            'errors' => $validator->errors()
        ], Response::HTTP_UNPROCESSABLE_ENTITY);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }
}
