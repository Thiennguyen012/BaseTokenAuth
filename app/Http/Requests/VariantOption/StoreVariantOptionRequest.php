<?php

namespace App\Http\Requests\VariantOption;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="StoreVariantOptionRequest",
 *     required={"product_variant_group_id","option_code","option_name"},
 *     @OA\Property(property="product_variant_group_id", type="integer", example=1),
 *     @OA\Property(property="option_code", type="string", maxLength=100, example="red"),
 *     @OA\Property(property="option_name", type="string", maxLength=255, example="Màu đỏ"),
 *     @OA\Property(property="sort_order", type="integer", minimum=0, nullable=true),
 *     @OA\Property(property="is_active", type="boolean", nullable=true)
 * )
 */
class StoreVariantOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $configurationId = $this->input('product_variant_group_id');

        return [
            'product_variant_group_id' => 'required|exists:product_variant_groups,id',
            'option_code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('variant_options', 'option_code')->where(fn($query) => $query->where('product_variant_group_id', $configurationId)),
            ],
            'option_name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'product_variant_group_id.required' => __('validation.custom.variant_option.variant_group_id.required'),
            'product_variant_group_id.exists' => __('validation.custom.variant_option.variant_group_id.exists'),
            'option_code.required' => __('validation.custom.variant_option.option_code.required'),
            'option_code.string' => __('validation.custom.variant_option.option_code.string'),
            'option_code.max' => __('validation.custom.variant_option.option_code.max'),
            'option_code.unique' => __('validation.custom.variant_option.option_code.unique'),
            'option_name.required' => __('validation.custom.variant_option.option_name.required'),
            'option_name.string' => __('validation.custom.variant_option.option_name.string'),
            'option_name.max' => __('validation.custom.variant_option.option_name.max'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => __('validation.custom.variant_option.invalid_data'),
            'errors' => $validator->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }
}
