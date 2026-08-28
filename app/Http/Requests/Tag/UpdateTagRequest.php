<?php

namespace App\Http\Requests\Tag;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="UpdateTagRequest",
 *     @OA\Property(property="name", type="string", maxLength=255),
 *     @OA\Property(property="slug", type="string", maxLength=255, nullable=true)
 * )
 */
class UpdateTagRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('slug')) {
            $this->merge(['slug' => Str::slug((string) $this->input('slug'))]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        if ($id && !is_numeric($id)) {
            $tag = app(\App\Services\Tag\TagService::class)->find((string) $id);
            $id = $tag?->id ?? $id;
        }

        return [
            'name' => 'sometimes|string|max:255',
            'tag_group_id' => 'nullable|exists:tag_groups,id',
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('tags', 'slug')->ignore($id),
            ],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => 'Dữ liệu không hợp lệ.',
            'errors' => $validator->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }
}
