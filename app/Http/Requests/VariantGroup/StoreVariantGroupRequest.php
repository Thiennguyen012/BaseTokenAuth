<?php

namespace App\Http\Requests\VariantGroup;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreVariantGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_code' => 'required|string|max:100|unique:variant_groups,group_code',
            'group_name' => 'required|string|max:255',
            'option_ids' => 'nullable|array',
            'option_ids.*' => 'required|integer|distinct|exists:variant_options,id',
        ];
    }

    public function messages(): array
    {
        return [
            'group_code.required' => __('validation.custom.variant_group.group_code.required'),
            'group_code.string' => __('validation.custom.variant_group.group_code.string'),
            'group_code.max' => __('validation.custom.variant_group.group_code.max'),
            'group_code.unique' => __('validation.custom.variant_group.group_code.unique'),
            'group_name.required' => __('validation.custom.variant_group.group_name.required'),
            'group_name.string' => __('validation.custom.variant_group.group_name.string'),
            'group_name.max' => __('validation.custom.variant_group.group_name.max'),
            'option_ids.array' => 'Danh sách option phải là một mảng.',
            'option_ids.*.integer' => 'ID option phải là số nguyên.',
            'option_ids.*.distinct' => 'Danh sách option không được chứa ID trùng nhau.',
            'option_ids.*.exists' => 'Một hoặc nhiều option không tồn tại.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => __('validation.custom.variant_group.invalid_data'),
            'errors' => $validator->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }
}
