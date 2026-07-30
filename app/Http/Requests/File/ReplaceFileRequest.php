<?php

namespace App\Http\Requests\File;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:jpeg,jpg,png,webp,svg,gif,mp4,mov,avi,mkv,webm|max:51200',
            'title' => 'sometimes|nullable|string|max:255',
            'type' => 'sometimes|nullable|string|max:100',
            'sort_order' => 'sometimes|integer|min:0',
        ];
    }
}
