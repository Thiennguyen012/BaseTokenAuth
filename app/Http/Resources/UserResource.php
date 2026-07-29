<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="UserResource",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="phone", type="string", nullable=true),
 *     @OA\Property(property="birthday", type="string", format="date", nullable=true),
 *     @OA\Property(property="address", type="string", nullable=true),
 *     @OA\Property(property="avatar", type="string", nullable=true),
 *     @OA\Property(property="status", type="integer"),
 *     @OA\Property(property="is_super_admin", type="boolean"),
 *     @OA\Property(property="role_ids", type="array", @OA\Items(type="integer")),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 * )
 */
class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'birthday' => optional($this->birthday)->toDateString(),
            'address' => $this->address,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'is_super_admin' => $this->is_super_admin,
            'role_ids' => $this->getRoleIds(),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }

    private function getRoleIds(): array
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->pluck('id')->values()->all();
        }

        return $this->roles()->pluck('roles.id')->values()->all();
    }
}
