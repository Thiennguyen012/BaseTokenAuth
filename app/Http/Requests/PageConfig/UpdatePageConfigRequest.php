<?php

namespace App\Http\Requests\PageConfig;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="UpdatePageConfigRequest",
 *     @OA\Property(property="company_name", type="string", maxLength=255),
 *     @OA\Property(property="slogan", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="addresses", type="array", @OA\Items(type="string", maxLength=255)),
 *     @OA\Property(property="map_url", type="string", format="uri", maxLength=2048, nullable=true),
 *     @OA\Property(property="hotline", type="string", maxLength=255),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255, nullable=true),
 *     @OA\Property(property="working_hour", type="string", maxLength=255),
 *     @OA\Property(property="socials", type="object"),
 *     @OA\Property(property="favicon", type="string", format="binary"),
 *     @OA\Property(property="logo", type="string", format="binary")
 * )
 */
class UpdatePageConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['sometimes', 'required', 'string', 'max:255'],
            'slogan' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'addresses' => ['sometimes', 'nullable', 'array'],
            'addresses.*' => ['required', 'string', 'max:255'],
            'map_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'hotline' => ['sometimes', 'nullable', 'numeric', 'max_digits:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'working_hour' => ['sometimes', 'nullable', 'string', 'max:255'],
            'socials' => ['sometimes', 'nullable', 'array'],
            'socials.*' => ['required', 'url', 'max:2048'],
            'favicon' => ['sometimes', 'array', 'max:1'],
            'favicon.*' => ['required', 'image', 'mimes:png,ico,jpeg,jpg,webp', 'max:2048'],
            'logo' => ['sometimes', 'array', 'max:1'],
            'logo.*' => ['required', 'image', 'mimes:png,jpeg,jpg,webp', 'max:5120'],
        ];
    }
}
