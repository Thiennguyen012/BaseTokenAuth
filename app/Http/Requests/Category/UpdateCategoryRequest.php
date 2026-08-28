<?php

namespace App\Http\Requests\Category;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('id');
        if ($categoryId && !is_numeric($categoryId)) {
            $category = app(\App\Services\Category\CategoryService::class)->find((string) $categoryId);
            $categoryId = $category?->id ?? $categoryId;
        }

        return [
            'category_name' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('categories', 'category_name')->ignore($categoryId),
            ],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'description' => 'sometimes|nullable|string|max:255',
            'thumbnail_path' => 'sometimes|nullable',
            'thumbnail' => 'sometimes|nullable|file|image|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'category_name.string' => __('validation.custom.category.category_name.string'),
            'category_name.max' => __('validation.custom.category.category_name.max'),
            'category_name.unique' => __('validation.custom.category.category_name.unique'),
            'description.string' => __('validation.custom.category.description.string'),
            'description.max' => __('validation.custom.category.description.max'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => __('validation.custom.category.invalid_data'),
            'errors' => $validator->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }
}
