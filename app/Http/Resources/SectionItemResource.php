<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="SectionItemResource",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="page_section_id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", nullable=true, example="Mục con 1"),
 *     @OA\Property(property="subtitle", type="string", nullable=true, example="Phụ đề mục con"),
 *     @OA\Property(property="content", type="string", nullable=true, example="Nội dung chi tiết mục con"),
 *     @OA\Property(property="sort_order", type="integer", example=0),
 *     @OA\Property(property="files", type="array", @OA\Items(ref="#/components/schemas/FileResource")),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 * )
 */
class SectionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'page_section_id' => $this->page_section_id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'content' => $this->content,
            'sort_order' => $this->sort_order,
            'files' => FileResource::collection($this->whenLoaded('files')),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
