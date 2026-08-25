<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="TagGroupResource",
 *   @OA\Property(property="id", type="integer"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="code", type="string"),
 *   @OA\Property(property="sort_order", type="integer"),
 *   @OA\Property(property="tags", type="array", @OA\Items(ref="#/components/schemas/TagResource"))
 * )
 */
class TagGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'sort_order' => (int) $this->sort_order,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
