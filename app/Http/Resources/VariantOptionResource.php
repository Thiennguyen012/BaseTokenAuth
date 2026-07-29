<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="VariantOptionResource",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="variant_group_id", type="integer"),
 *     @OA\Property(property="option_code", type="string"),
 *     @OA\Property(property="option_name", type="string"),
 *     @OA\Property(property="sort_order", type="integer"),
 *     @OA\Property(property="is_active", type="boolean"),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 * )
 */
class VariantOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'variant_group_id' => $this->variant_group_id,
            'option_code' => $this->option_code,
            'option_name' => $this->option_name,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
