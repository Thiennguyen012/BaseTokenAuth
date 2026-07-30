<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="PageSectionResource",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="page_content_id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", nullable=true, example="Mục trang 1"),
 *     @OA\Property(property="subtitle", type="string", nullable=true, example="Phụ đề mục trang"),
 *     @OA\Property(property="content", type="string", nullable=true, example="Nội dung chính"),
 *     @OA\Property(property="sort_order", type="integer", example=0),
 *     @OA\Property(property="files", type="array", @OA\Items(ref="#/components/schemas/FileResource")),
 *     @OA\Property(property="items", type="array", @OA\Items(ref="#/components/schemas/SectionItemResource")),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 * )
 */
class PageSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'page_content_id' => $this->page_content_id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'content' => $this->content,
            'sort_order' => $this->sort_order,
            'files' => FileResource::collection($this->whenLoaded('files')),
            'items' => SectionItemResource::collection($this->whenLoaded('items')),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
