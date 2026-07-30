<?php

namespace App\Http\Requests\SectionItem;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="UpdateSectionItemRequest",
 *     @OA\Property(property="page_section_id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="title", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="subtitle", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="content", type="string", nullable=true),
 *     @OA\Property(
 *         property="files[]",
 *         type="array",
 *         description="Danh sách các tệp tin ảnh hoặc video tải lên",
 *         @OA\Items(type="string", format="binary")
 *     ),
 *     @OA\Property(property="video_urls[]", type="array", @OA\Items(type="string", format="uri")),
 *     @OA\Property(property="sort_order", type="integer", nullable=true)
 * )
 */
class UpdateSectionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page_section_id' => 'sometimes|required|integer|exists:page_sections,id',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'files' => 'sometimes|array|max:20',
            'files.*' => 'required|file|mimes:jpeg,jpg,png,webp,svg,gif,mp4,mov,avi,mkv,webm|max:51200',
            'video_urls' => 'sometimes|array|max:20',
            'video_urls.*' => 'required|url|max:2048',
            'sort_order' => 'nullable|integer|min:0',
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
