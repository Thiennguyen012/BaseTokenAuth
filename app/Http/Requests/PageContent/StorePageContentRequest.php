<?php

namespace App\Http\Requests\PageContent;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="StorePageContentRequest",
 *     required={"slug", "title"},
 *     @OA\Property(property="slug", type="string", maxLength=255, example="trang-chu"),
 *     @OA\Property(property="title", type="string", maxLength=255, example="Trang chủ")
 * )
 */
class StorePageContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => 'required|string|max:255|unique:page_contents,slug',
            'title' => 'required|string|max:255',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => 'Dữ liệu không hợp lệ',
            'errors' => $validator->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }
}
