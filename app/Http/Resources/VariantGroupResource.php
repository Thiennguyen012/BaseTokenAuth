<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="VariantGroupResource",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="group_code", type="string"),
 *     @OA\Property(property="group_name", type="string"),
 *     @OA\Property(property="sort_order", type="integer", nullable=true, description="Chỉ có khi group thuộc product"),
 *     @OA\Property(property="is_required", type="boolean", nullable=true, description="Chỉ có khi group thuộc product"),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 * )
 */
class VariantGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'group_code' => $this->group_code,
            'group_name' => $this->group_name,
            'sort_order' => $this->whenPivotLoaded('product_variant_groups', fn () => $this->pivot->sort_order),
            'is_required' => $this->whenPivotLoaded('product_variant_groups', fn () => (bool) $this->pivot->is_required),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
