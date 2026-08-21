<?php

namespace App\Http\Requests\Category;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_name' => 'required|string|max:100|unique:categories,category_name',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'description' => 'nullable|string|max:255',
            'thumbnail_path' => 'nullable',
            'thumbnail' => 'nullable|file|image|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'category_name.required' => __('validation.custom.category.category_name.required'),
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
