<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'type' => $this->type,
            'store_name' => $this->store_name,
            'store_type' => $this->store_type,
            'avatar' => $this->avatar,
            'city' => $this->city,
            'area' => $this->area,
            'address' => $this->address,
            'is_verified' => $this->is_verified,
            'is_active' => $this->is_active,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'addresses' => AddressResource::collection($this->whenLoaded('addresses')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
