<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_en' => $this->name_en,
            'slug' => $this->slug,
            'description' => $this->description,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price' => (float) $this->price,
            'compare_price' => $this->compare_price ? (float) $this->compare_price : null,
            'discount_percent' => $this->discount_percent,
            'unit' => $this->unit,
            'unit_quantity' => $this->unit_quantity,
            'min_order_quantity' => $this->min_order_quantity,
            'stock_quantity' => $this->stock_quantity,
            'is_low_stock' => $this->is_low_stock,
            'is_out_of_stock' => $this->is_out_of_stock,
            'images' => $this->images,
            'thumbnail' => $this->thumbnail,
            'is_featured' => $this->is_featured,
            'views_count' => $this->views_count,
            'sales_count' => $this->sales_count,
            'rating' => (float) $this->rating,
            'total_ratings' => $this->total_ratings,
            'tags' => $this->tags,
            'attributes' => $this->attributes,
            'expiry_date' => $this->expiry_date?->toDateString(),
            'manufacturer' => $this->manufacturer,
            'country_of_origin' => $this->country_of_origin,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
