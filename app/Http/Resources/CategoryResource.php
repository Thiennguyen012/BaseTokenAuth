<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="CategoryResource",
 *   @OA\Property(property="id", type="integer"),
 *   @OA\Property(property="category_name", type="string"),
 *   @OA\Property(property="description", type="string", nullable=true),
 *   @OA\Property(property="sort_order", type="integer")
 * )
 */

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_name' => $this->category_name,
            'slug' => $this->slug,
            'description' => $this->description,
            'thumbnail_path' => $this->thumbnail_path,
            'thumbnail_url' => $this->thumbnail_path
                ? (filter_var($this->thumbnail_path, FILTER_VALIDATE_URL) ? $this->thumbnail_path : asset('storage/' . ltrim($this->thumbnail_path, '/')))
                : null,
            'first_image' => $this->thumbnail_path ? ['path' => $this->thumbnail_path] : null,
            'sort_order' => $this->whenPivotLoaded('product_categories', fn () => (int) $this->pivot->sort_order),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
