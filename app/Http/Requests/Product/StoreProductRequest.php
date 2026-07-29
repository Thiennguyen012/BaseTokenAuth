<?php

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'variant_groups' => 'sometimes|array',
            'variant_groups.*.variant_group_id' => 'required|integer|distinct|exists:variant_groups,id',
            'variant_groups.*.is_required' => 'sometimes|boolean',
            'variant_groups.*.sort_order' => 'sometimes|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'product_name.required' => __('validation.custom.product.product_name.required'),
            'product_name.string' => __('validation.custom.product.product_name.string'),
            'product_name.max' => __('validation.custom.product.product_name.max'),
            'sku.string' => __('validation.custom.product.sku.string'),
            'sku.max' => __('validation.custom.product.sku.max'),
            'sku.unique' => __('validation.custom.product.sku.unique'),
            'description.string' => __('validation.custom.product.description.string'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => __('validation.custom.product.invalid_data'),
            'errors' => $validator->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }
}
