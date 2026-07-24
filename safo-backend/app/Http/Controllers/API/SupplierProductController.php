<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;

/**
 * Supplier product controller — manage supplier's own products.
 */
class SupplierProductController extends Controller
{
    /**
     * Get the authenticated supplier's record.
     */
    private function getSupplier(Request $request): Supplier
    {
        return Supplier::where('user_id', $request->user()->id)->firstOrFail();
    }

    /**
     * List supplier's products.
     */
    public function index(Request $request)
    {
        $supplier = $this->getSupplier($request);

        $request->validate([
            'status' => 'nullable|in:active,inactive,all',
            'category_id' => 'nullable|exists:categories,id',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $products = Product::query()
            ->where('supplier_id', $supplier->id)
            ->with(['category'])
            ->when($request->input('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->input('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->has('category_id'), fn ($q) => $q->byCategory($request->category_id))
            ->search($request->input('search'))
            ->latest()
            ->paginate($request->input('per_page', 20));

        return ProductResource::collection($products);
    }

    /**
     * Add a new product.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'description_en' => 'nullable|string|max:2000',
            'category_id' => 'required|exists:categories,id',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'barcode' => 'nullable|string|max:100|unique:products,barcode',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0|gt:price',
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
        ]);

        $supplier = $this->getSupplier($request);
        $validated['supplier_id'] = $supplier->id;

        $product = Product::create($validated);
        $product->load('category');

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة المنتج',
            'data' => new ProductResource($product),
        ], 201);
    }

    /**
     * Update a product.
     */
    public function update(Request $request, Product $product)
    {
        $supplier = $this->getSupplier($request);

        if ($product->supplier_id !== $supplier->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'description_en' => 'nullable|string|max:2000',
            'category_id' => 'sometimes|exists:categories,id',
            'sku' => 'nullable|string|max:100|unique:products,sku,' . $product->id,
            'barcode' => 'nullable|string|max:100|unique:products,barcode,' . $product->id,
            'price' => 'sometimes|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'unit' => 'sometimes|string|max:50',
            'unit_quantity' => 'sometimes|integer|min:1',
            'min_order_quantity' => 'sometimes|integer|min:1',
            'stock_quantity' => 'sometimes|integer|min:0',
            'low_stock_threshold' => 'sometimes|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'weight_unit' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'attributes' => 'nullable|array',
            'expiry_date' => 'nullable|date',
            'manufacturer' => 'nullable|string|max:255',
            'country_of_origin' => 'nullable|string|max:100',
            'images' => 'nullable|array',
            'images.*' => 'string|max:500',
            'thumbnail' => 'nullable|string|max:500',
        ]);

        $product->update($validated);
        $product->load('category');

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المنتج',
            'data' => new ProductResource($product->fresh()),
        ]);
    }

    /**
     * Soft-delete a product.
     */
    public function destroy(Request $request, Product $product)
    {
        $supplier = $this->getSupplier($request);

        if ($product->supplier_id !== $supplier->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        // Check if product has active orders
        $hasActiveOrders = $product->orderItems()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', ['pending', 'confirmed', 'processing', 'ready', 'shipped'])
            ->exists();

        if ($hasActiveOrders) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف المنتج لوجود طلبية نشطة',
            ], 422);
        }

        // Remove from all carts first
        $product->cartItems()->delete();

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المنتج',
        ]);
    }

    /**
     * Update product stock quantity.
     */
    public function updateStock(Request $request, Product $product)
    {
        $supplier = $this->getSupplier($request);

        if ($product->supplier_id !== $supplier->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
            'action' => 'sometimes|in:set,add,subtract',
        ]);

        $action = $validated['action'] ?? 'set';

        match ($action) {
            'set' => $product->update(['stock_quantity' => $validated['quantity']]),
            'add' => $product->increment('stock_quantity', $validated['quantity']),
            'subtract' => $product->decrement('stock_quantity', $validated['quantity']),
        };

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المخزون',
            'data' => [
                'stock_quantity' => $product->fresh()->stock_quantity,
                'is_low_stock' => $product->fresh()->is_low_stock,
            ],
        ]);
    }
}
