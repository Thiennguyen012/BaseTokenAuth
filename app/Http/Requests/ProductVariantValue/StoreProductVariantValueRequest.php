<?php

namespace App\Http\Requests\ProductVariantValue;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class StoreProductVariantValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_variant_id' => 'required|exists:product_variants,id',
            'variant_group_id' => 'required|exists:variant_groups,id',
            'variant_option_id' => [
                'required',
                Rule::exists('variant_options', 'id')->where(fn($query) => $query->where('variant_group_id', $this->input('variant_group_id'))),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'product_variant_id.required' => __('validation.custom.product_variant_value.product_variant_id.required'),
            'product_variant_id.exists' => __('validation.custom.product_variant_value.product_variant_id.exists'),
            'variant_group_id.required' => __('validation.custom.product_variant_value.variant_group_id.required'),
            'variant_group_id.exists' => __('validation.custom.product_variant_value.variant_group_id.exists'),
            'variant_option_id.required' => __('validation.custom.product_variant_value.variant_option_id.required'),
            'variant_option_id.exists' => __('validation.custom.product_variant_value.variant_option_id.exists'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => __('validation.custom.product_variant_value.invalid_data'),
            'errors' => $validator->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }
}
