<?php

namespace App\Http\Requests\CustomerContact;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="StoreCustomerContactRequest",
 *     required={"full_name", "phone", "consultation_content"},
 *     @OA\Property(property="full_name", type="string", maxLength=255, example="Nguyễn Văn A"),
 *     @OA\Property(property="phone", type="string", maxLength=30, example="0901234567"),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255, nullable=true),
 *     @OA\Property(property="category_id", type="integer", nullable=true),
 *     @OA\Property(property="consultation_content", type="string", maxLength=5000)
 * )
 */
class StoreCustomerContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\s().-]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'consultation_content' => ['required', 'string', 'max:5000'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ValidationException($validator, response()->json([
            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => 'Dữ liệu liên hệ không hợp lệ.',
            'errors' => $validator->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
