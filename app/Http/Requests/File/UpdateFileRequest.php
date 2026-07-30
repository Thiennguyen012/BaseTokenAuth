<?php

namespace App\Http\Requests\File;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'file_name' => 'sometimes|nullable|string|max:255',
            'type' => 'sometimes|nullable|string|max:100',
            'external_url' => 'sometimes|required|url|max:2048',
            'sort_order' => 'sometimes|required|integer|min:0',
        ];
    }
}
