<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="PageConfigResource",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="company_name", type="string"),
 *     @OA\Property(property="addresses", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="hotline", type="string", nullable=true),
 *     @OA\Property(property="working_hour", type="string", nullable=true),
 *     @OA\Property(property="socials", type="object", nullable=true),
 *     @OA\Property(property="favicon_path", type="string", nullable=true),
 *     @OA\Property(property="logo_path", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class PageConfigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'addresses' => $this->addresses ?? [],
            'hotline' => $this->hotline,
            'working_hour' => $this->working_hour,
            'socials' => $this->socials ?? [],
            'favicon_path' => $this->favicon_path,
            'logo_path' => $this->logo_path,
            'favicon' => FileResource::collection($this->whenLoaded('files', fn () => $this->files->where('type', 'favicon')->values())),
            'logo' => FileResource::collection($this->whenLoaded('files', fn () => $this->files->where('type', 'logo')->values())),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
