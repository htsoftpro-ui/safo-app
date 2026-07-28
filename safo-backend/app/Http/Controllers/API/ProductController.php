<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Product controller — public endpoints for browsing products.
 */
class ProductController extends Controller
{
    /**
     * List products with search, filters, sorting, and pagination.
     */
    public function index(Request $request)
    {
        $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'search' => 'nullable|string|max:255',
            'sort' => 'nullable|in:price,rating,sales_count,created_at,name',
            'order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $products = Product::query()
            ->active()
            ->inStock()
            ->whereHas('supplier', fn ($q) => $q->where('is_active', true))
            ->whereHas('supplier.user', fn ($q) => $q->where('is_active', true))
            ->with(['supplier', 'category'])
            ->search($request->input('search'))
            ->when($request->has('category_id'), fn ($q) => $q->byCategory($request->category_id))
            ->when($request->has('supplier_id'), fn ($q) => $q->bySupplier($request->supplier_id))
            ->priceRange(
                $request->input('min_price') ? (float) $request->min_price : null,
                $request->input('max_price') ? (float) $request->max_price : null,
            )
            ->sorted(
                $request->input('sort', 'created_at'),
                $request->input('order', 'desc'),
            )
            ->paginate($request->input('per_page', 20));

        return ProductResource::collection($products);
    }

    /**
     * Show single product with supplier and reviews.
     */
    public function show(Product $product)
    {
        $product->load(['supplier', 'category', 'reviews' => fn ($q) => $q->latest()->limit(10)]);

        // Increment views
        $product->increment('views_count');

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Featured products.
     */
    public function featured(Request $request)
    {
        $products = Product::query()
            ->active()
            ->inStock()
            ->featured()
            ->whereHas('supplier', fn ($q) => $q->where('is_active', true))
            ->whereHas('supplier.user', fn ($q) => $q->where('is_active', true))
            ->with(['supplier'])
            ->sorted('rating', 'desc')
            ->paginate($request->input('per_page', 10));

        return ProductResource::collection($products);
    }

    /**
     * New arrivals.
     */
    public function newArrivals(Request $request)
    {
        $products = Product::query()
            ->active()
            ->inStock()
            ->whereHas('supplier', fn ($q) => $q->where('is_active', true))
            ->whereHas('supplier.user', fn ($q) => $q->where('is_active', true))
            ->with(['supplier'])
            ->latest()
            ->paginate($request->input('per_page', 20));

        return ProductResource::collection($products);
    }

    /**
     * Best sellers.
     */
    public function bestSellers(Request $request)
    {
        $products = Product::query()
            ->active()
            ->inStock()
            ->whereHas('supplier', fn ($q) => $q->where('is_active', true))
            ->whereHas('supplier.user', fn ($q) => $q->where('is_active', true))
            ->with(['supplier'])
            ->orderByDesc('sales_count')
            ->paginate($request->input('per_page', 20));

        return ProductResource::collection($products);
    }

    /**
     * Advanced search with full-text matching.
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $products = Product::query()
            ->active()
            ->inStock()
            ->whereHas('supplier', fn ($q) => $q->where('is_active', true))
            ->whereHas('supplier.user', fn ($q) => $q->where('is_active', true))
            ->with(['supplier'])
            ->search($request->input('q'))
            ->when($request->has('category_id'), fn ($q) => $q->byCategory($request->category_id))
            ->sorted('sales_count', 'desc')
            ->paginate($request->input('per_page', 20));

        return ProductResource::collection($products);
    }
}
