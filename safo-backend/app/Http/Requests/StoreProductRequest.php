<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'description_en' => 'nullable|string|max:2000',
            'category_id' => 'required|exists:categories,id',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'barcode' => 'nullable|string|max:100|unique:products,barcode',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'unit' => 'required|string|max:50',
            'unit_quantity' => 'sometimes|integer|min:1',
            'min_order_quantity' => 'sometimes|integer|min:1',
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'sometimes|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'weight_unit' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'attributes' => 'nullable|array',
            'expiry_date' => 'nullable|date|after:today',
            'manufacturer' => 'nullable|string|max:255',
            'country_of_origin' => 'nullable|string|max:100',
            'images' => 'nullable|array',
            'images.*' => 'string|max:500',
            'thumbnail' => 'nullable|string|max:500',
        ];
    }
}
