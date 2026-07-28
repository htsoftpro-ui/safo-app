<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class AdminCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::withCount('products', 'children')
            ->when($request->has('search'), fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->ordered()
            ->get();

        return response()->json(['success' => true, 'data' => CategoryResource::collection($categories)]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $category = Category::create([
            'name' => $request->name,
            'name_en' => $request->name_en,
            'parent_id' => $request->parent_id,
            'description' => $request->description,
            'icon' => $request->icon,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة الفئة',
            'data' => new CategoryResource($category),
        ], 201);
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $category->update($request->only([
            'name', 'name_en', 'parent_id', 'description', 'icon', 'sort_order', 'is_active',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الفئة',
            'data' => new CategoryResource($category->fresh()),
        ]);
    }

    public function destroy(Category $category)
    {
        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف فئة تحتوي على منتجات',
            ], 422);
        }

        if ($category->children()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف فئة تحتوي على فئات فرعية',
            ], 422);
        }

        $category->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف الفئة']);
    }
}
