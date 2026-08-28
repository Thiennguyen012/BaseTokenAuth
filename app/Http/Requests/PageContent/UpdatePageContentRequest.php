<?php

namespace App\Http\Requests\PageContent;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="UpdatePageContentRequest",
 *     @OA\Property(property="slug", type="string", maxLength=255, example="trang-gioi-thieu"),
 *     @OA\Property(property="title", type="string", maxLength=255, example="Trang giới thiệu")
 * )
 */
class UpdatePageContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('page_content');
        if ($id && !is_numeric($id)) {
            $pageContent = app(\App\Services\PageContent\PageContentService::class)->findBySlug((string) $id);
            $id = $pageContent?->id ?? $id;
        }

        return [
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('page_contents', 'slug')->ignore($id),
            ],
            'title' => 'sometimes|required|string|max:255',
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
