<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ProductVariantResource",
 *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="product_id", type="integer"),
 *     @OA\Property(property="product_name", type="string"),
 *     @OA\Property(property="sku", type="string"),
 *     @OA\Property(property="price", type="string", nullable=true, example="150000.00"),
 *     @OA\Property(property="stock", type="integer"),
 *     @OA\Property(property="is_active", type="boolean"),
 *     @OA\Property(property="images", type="array", @OA\Items(ref="#/components/schemas/FileResource")),
 *     @OA\Property(property="first_image", nullable=true, ref="#/components/schemas/FileResource"),
 *     @OA\Property(property="options", type="array", @OA\Items(ref="#/components/schemas/VariantOptionResource")),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 * )
 */
class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product?->product_name),
            'sku' => $this->sku,
            'price' => $this->price,
            'stock' => $this->stock,
            'is_active' => $this->is_active,
            'images' => $this->whenLoaded(
                'files',
                fn () => FileResource::collection($this->files->where('type', 'image')->values())
            ),
            'first_image' => $this->whenLoaded('files', function () {
                $image = $this->files->firstWhere('type', 'image');

                return $image ? new FileResource($image) : null;
            }),
            'options' => $this->whenLoaded('options', fn () => VariantOptionResource::collection($this->options)),
            'option_names' => $this->whenLoaded('options', fn () => $this->options->map(fn ($option) => ($option->productVariantGroup?->group?->group_name ? $option->productVariantGroup->group->group_name . ': ' : '') . $option->option_name)->implode(', ')),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
