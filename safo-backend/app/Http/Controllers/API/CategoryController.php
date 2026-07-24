<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use Illuminate\Http\Request;

/**
 * Category controller — browse product categories.
 */
class CategoryController extends Controller
{
    /**
     * List all active categories with children counts.
     */
    public function index()
    {
        $categories = Category::active()
            ->root()
            ->ordered()
            ->withCount('children')
            ->withCount('products')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Show single category with its children.
     */
    public function show(Category $category)
    {
        $category->load(['children' => fn ($q) => $q->active()->ordered()]);
        $category->loadCount('products');

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * List products in a category with pagination.
     */
    public function products(Request $request, Category $category)
    {
        $request->validate([
            'sort' => 'nullable|in:price,rating,sales_count,created_at,name',
            'order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $products = $category->products()
            ->active()
            ->inStock()
            ->with(['supplier'])
            ->sorted(
                $request->input('sort', 'created_at'),
                $request->input('order', 'desc'),
            )
            ->paginate($request->input('per_page', 20));

        return ProductResource::collection($products);
    }
}
