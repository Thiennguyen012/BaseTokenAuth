<?php

namespace App\Http\Requests\ProductVariant;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="UpdateProductVariantRequest",
 *     @OA\Property(property="_method", type="string", example="PUT"),
 *     @OA\Property(property="product_id", type="integer"),
 *     @OA\Property(property="sku", type="string", maxLength=100),
 *     @OA\Property(property="price", type="number", format="decimal", minimum=0, nullable=true),
 *     @OA\Property(property="stock", type="integer", minimum=0, nullable=true),
 *     @OA\Property(property="is_active", type="boolean", nullable=true),
 *     @OA\Property(property="is_contact_price", type="boolean", nullable=true),
 *     @OA\Property(property="option_ids[]", type="array", @OA\Items(type="integer")),
 *     @OA\Property(property="images[]", type="array", maxItems=10, @OA\Items(type="string", format="binary"))
 * )
 */
class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'sometimes|exists:products,id',
            'sku' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('product_variants', 'sku')->ignore($this->route('id')),
            ],
            'price' => 'sometimes|nullable|numeric|min:0',
            'stock' => 'sometimes|nullable|integer|min:0',
            'is_active' => 'sometimes|nullable|boolean',
            'is_contact_price' => 'sometimes|nullable|boolean',
            'option_ids' => 'sometimes|array',
            'option_ids.*' => 'required|integer|distinct|exists:variant_options,id',
            'images' => 'sometimes|array|max:10',
            'images.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
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
