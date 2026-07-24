<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'logo' => $this->logo,
            'rating' => (float) $this->rating,
            'total_ratings' => $this->total_ratings,
            'is_verified' => $this->is_verified,
        ];
    }
}
