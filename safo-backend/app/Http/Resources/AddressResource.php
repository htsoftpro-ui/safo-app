<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'address' => $this->address,
            'city' => $this->city,
            'area' => $this->area,
            'building' => $this->building,
            'floor' => $this->floor,
            'apartment' => $this->apartment,
            'landmark' => $this->landmark,
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'is_default' => $this->is_default,
            'full_address' => $this->full_address,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
