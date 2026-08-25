<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="TagResource",
 *   @OA\Property(property="id", type="integer"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="slug", type="string")
 * )
 */
class TagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tag_group_id' => $this->tag_group_id ? (int) $this->tag_group_id : null,
            'tag_group_name' => $this->tagGroup?->name,
            'name' => $this->name,
            'slug' => $this->slug,
            'tag_group' => $this->whenLoaded('tagGroup', fn () => [
                'id' => $this->tagGroup->id,
                'name' => $this->tagGroup->name,
                'code' => $this->tagGroup->code,
            ]),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
