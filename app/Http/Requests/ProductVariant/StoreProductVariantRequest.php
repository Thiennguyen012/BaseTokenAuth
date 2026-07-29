<?php

namespace App\Http\Requests\ProductVariant;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'sku' => 'required|string|max:100|unique:product_variants,sku',
            'price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'option_ids' => 'required|array',
            'option_ids.*' => 'required|integer|distinct|exists:variant_options,id',
            'images' => 'sometimes|array|max:10',
            'images.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => __('validation.custom.product_variant.product_id.required'),
            'product_id.exists' => __('validation.custom.product_variant.product_id.exists'),
            'sku.string' => __('validation.custom.product_variant.sku.string'),
            'sku.max' => __('validation.custom.product_variant.sku.max'),
            'sku.unique' => __('validation.custom.product_variant.sku.unique'),
            'price.numeric' => __('validation.custom.product_variant.price.numeric'),
            'price.min' => __('validation.custom.product_variant.price.min'),
            'stock.integer' => __('validation.custom.product_variant.stock.integer'),
            'stock.min' => __('validation.custom.product_variant.stock.min'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => __('validation.custom.product_variant.invalid_data'),
            'errors' => $validator->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }
}
