<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'is_active' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $products = Product::query()
            ->with(['supplier', 'category'])
            ->when($request->has('search'), fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->has('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->has('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->latest()
            ->paginate($request->input('per_page', 20));

        return ProductResource::collection($products);
    }

    public function show(Product $product)
    {
        $product->load(['supplier', 'category', 'reviews.user']);

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ]);
    }

    public function toggleActive(Product $product)
    {
        $product->update(['is_active' => !$product->is_active]);

        return response()->json([
            'success' => true,
            'message' => $product->is_active ? 'تم تفعيل المنتج' : 'تم تعطيل المنتج',
            'data' => ['is_active' => $product->is_active],
        ]);
    }

    public function toggleFeatured(Product $product)
    {
        $product->update(['is_featured' => !$product->is_featured]);

        return response()->json([
            'success' => true,
            'message' => $product->is_featured ? 'تم إضافة المنتج للمميزة' : 'تم إزالة المنتج من المميزة',
            'data' => ['is_featured' => $product->is_featured],
        ]);
    }

    public function destroy(Product $product)
    {
        $product->cartItems()->delete();
        $product->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف المنتج']);
    }
}
