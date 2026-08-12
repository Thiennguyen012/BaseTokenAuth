<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ProductVariantGroupResource",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="variant_group_id", type="integer"),
 *     @OA\Property(property="group_code", type="string"),
 *     @OA\Property(property="group_name", type="string"),
 *     @OA\Property(property="is_required", type="boolean"),
 *     @OA\Property(property="sort_order", type="integer"),
 *     @OA\Property(property="options", type="array", @OA\Items(ref="#/components/schemas/VariantOptionResource"))
 * )
 */
class ProductVariantGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'variant_group_id' => $this->variant_group_id,
            'group_code' => $this->whenLoaded('group', fn () => $this->group->group_code),
            'group_name' => $this->whenLoaded('group', fn () => $this->group->group_name),
            'is_required' => $this->is_required,
            'sort_order' => $this->sort_order,
            'options' => VariantOptionResource::collection($this->whenLoaded('options')),
        ];
    }
}
