<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ProductResource",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="product_name", type="string"),
 *     @OA\Property(property="slug", type="string", nullable=true),
 *     @OA\Property(property="sku", type="string", nullable=true),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="is_active", type="boolean"),
 *     @OA\Property(property="is_featured", type="boolean"),
 *     @OA\Property(property="is_contact_price", type="boolean"),
 *     @OA\Property(property="categories", type="array", @OA\Items(ref="#/components/schemas/CategoryResource")),
 *     @OA\Property(property="images", type="array", @OA\Items(ref="#/components/schemas/FileResource")),
 *     @OA\Property(property="first_image", nullable=true, ref="#/components/schemas/FileResource"),
 *     @OA\Property(property="variant_groups", type="array", @OA\Items(ref="#/components/schemas/ProductVariantGroupResource")),
 *     @OA\Property(property="variants", type="array", @OA\Items(ref="#/components/schemas/ProductVariantResource")),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 * )
 */
class ProductResource extends JsonResource
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
            'product_name' => $this->product_name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'is_featured' => (bool) $this->is_featured,
            'is_contact_price' => (bool) $this->is_contact_price,
            'categories' => $this->whenLoaded('categories', fn () => CategoryResource::collection($this->categories)),
            'category_names' => $this->whenLoaded('categories', fn () => $this->categories->pluck('category_name')->implode(', ')),
            'tags' => $this->whenLoaded('tags', fn () => TagResource::collection($this->tags)),
            'tag_names' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')->implode(', ')),
            'images' => $this->whenLoaded(
                'files',
                fn () => FileResource::collection($this->files->where('type', 'image')->values())
            ),
            'first_image' => $this->whenLoaded('files', function () {
                $image = $this->files->firstWhere('type', 'image');

                return $image ? new FileResource($image) : null;
            }),
            'variant_groups' => $this->whenLoaded(
                'variantGroupConfigurations',
                fn () => ProductVariantGroupResource::collection($this->variantGroupConfigurations)
            ),
            'variants' => $this->whenLoaded('variants', fn () => ProductVariantResource::collection($this->variants)),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
