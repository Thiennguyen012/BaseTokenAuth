<?php

namespace App\Http\Requests\VariantOption;

use App\Models\Variants\VariantOption;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UpdateVariantOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $variantOptionId = $this->route('id');
        $currentGroupId = VariantOption::query()->whereKey($variantOptionId)->value('variant_group_id');
        $variantGroupId = $this->input('variant_group_id', $currentGroupId);

        return [
            'variant_group_id' => 'sometimes|exists:variant_groups,id',
            'option_code' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('variant_options', 'option_code')
                    ->where(fn ($query) => $query->where('variant_group_id', $variantGroupId))
                    ->ignore($variantOptionId),
            ],
            'option_name' => 'sometimes|string|max:255',
            'sort_order' => 'sometimes|nullable|integer|min:0',
            'is_active' => 'sometimes|nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'variant_group_id.exists' => __('validation.custom.variant_option.variant_group_id.exists'),
            'option_code.string' => __('validation.custom.variant_option.option_code.string'),
            'option_code.max' => __('validation.custom.variant_option.option_code.max'),
            'option_code.unique' => __('validation.custom.variant_option.option_code.unique'),
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
