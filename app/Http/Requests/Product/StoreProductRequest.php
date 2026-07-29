<?php

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="StoreProductRequest",
 *     required={"product_name"},
 *     @OA\Property(property="product_name", type="string", maxLength=255, example="Áo thun basic"),
 *     @OA\Property(property="sku", type="string", maxLength=100, nullable=true, example="AT-001"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="is_active", type="boolean", nullable=true, default=true),
 *     @OA\Property(property="is_featured", type="boolean", nullable=true, default=false),
 *     @OA\Property(
 *         property="variant_groups",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             required={"variant_group_id"},
 *             @OA\Property(property="variant_group_id", type="integer"),
 *             @OA\Property(property="is_required", type="boolean"),
 *             @OA\Property(property="sort_order", type="integer", minimum=0)
 *         )
 *     ),
 *     @OA\Property(property="images[]", type="array", maxItems=10, @OA\Items(type="string", format="binary"))
 * )
 */
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
            'images' => 'sometimes|array|max:10',
            'images.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
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
