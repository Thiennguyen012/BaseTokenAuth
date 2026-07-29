<?php

namespace App\Http\Requests\VariantGroup;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="UpdateVariantGroupRequest",
 *     @OA\Property(property="group_code", type="string", maxLength=100),
 *     @OA\Property(property="group_name", type="string", maxLength=255),
 *     @OA\Property(property="option_ids", type="array", @OA\Items(type="integer"))
 * )
 */
class UpdateVariantGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_code' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('variant_groups', 'group_code')->ignore($this->route('id')),
            ],
            'group_name' => 'sometimes|string|max:255',
            'option_ids' => 'sometimes|array',
            'option_ids.*' => 'required|integer|distinct|exists:variant_options,id',
        ];
    }

    public function messages(): array
    {
        return [
            'group_code.string' => __('validation.custom.variant_group.group_code.string'),
            'group_code.max' => __('validation.custom.variant_group.group_code.max'),
            'group_code.unique' => __('validation.custom.variant_group.group_code.unique'),
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
