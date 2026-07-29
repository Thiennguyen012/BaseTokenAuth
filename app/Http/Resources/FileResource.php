<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="FileResource",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="file_name", type="string", nullable=true),
 *     @OA\Property(property="disk", type="string", example="public"),
 *     @OA\Property(property="path", type="string", example="products/1/image.jpg"),
 *     @OA\Property(property="mime_type", type="string", example="image/jpeg"),
 *     @OA\Property(property="size", type="integer", format="int64"),
 *     @OA\Property(property="type", type="string", nullable=true, example="image"),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true)
 * )
 */
class FileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'file_name' => $this->file_name,
            'disk' => $this->disk,
            'path' => $this->path,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'type' => $this->type,
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
