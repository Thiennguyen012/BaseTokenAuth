<?php

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="UpdateProductRequest",
 *     @OA\Property(property="_method", type="string", example="PUT"),
 *     @OA\Property(property="product_name", type="string", maxLength=255),
 *     @OA\Property(property="slug", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="sku", type="string", maxLength=100, nullable=true),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="is_active", type="boolean"),
 *     @OA\Property(property="is_featured", type="boolean"),
 *     @OA\Property(property="category_ids", type="array", @OA\Items(type="integer")),
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
class UpdateProductRequest extends FormRequest
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
        $productId = $this->route('id');

        return [
            'product_name' => 'sometimes|string|max:255',
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'sku' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'description' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'category_ids' => 'sometimes|array',
            'category_ids.*' => 'required|integer|distinct|exists:categories,id',
            'tag_ids' => 'sometimes|array',
            'tag_ids.*' => 'required|integer|distinct|exists:tags,id',
            'variant_groups' => 'sometimes|array',
            'variant_groups.*.variant_group_id' => 'required|integer|distinct|exists:variant_groups,id',
            'variant_groups.*.is_required' => 'sometimes|boolean',
            'variant_groups.*.sort_order' => 'sometimes|integer|min:0',
            'variant_groups.*.options' => 'sometimes|array',
            'variant_groups.*.options_present' => 'sometimes|boolean',
            'variant_groups.*.options.*.id' => 'sometimes|integer',
            'variant_groups.*.options.*.option_code' => 'required|string|max:100',
            'variant_groups.*.options.*.option_name' => 'required|string|max:255',
            'variant_groups.*.options.*.sort_order' => 'sometimes|integer|min:0',
            'variant_groups.*.options.*.is_active' => 'sometimes|boolean',
            'images' => 'sometimes|array|max:10',
            'images.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'product_name.string' => __('validation.custom.product.product_name.string'),
            'product_name.max' => __('validation.custom.product.product_name.max'),
            'slug.unique' => 'Slug sản phẩm đã tồn tại.',
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
