<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
