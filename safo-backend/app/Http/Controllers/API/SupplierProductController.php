<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierProductController extends Controller
{
    private function getSupplier(Request $request): Supplier
    {
        return Supplier::where('user_id', $request->user()->id)->firstOrFail();
    }

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

    public function store(StoreProductRequest $request)
    {
        $supplier = $this->getSupplier($request);
        $data = $request->validated();
        $data['supplier_id'] = $supplier->id;

        $product = Product::create($data);
        $product->load('category');

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة المنتج',
            'data' => new ProductResource($product),
        ], 201);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);
        $product->update($request->validated());
        $product->load('category');

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المنتج',
            'data' => new ProductResource($product->fresh()),
        ]);
    }

    public function destroy(Request $request, Product $product)
    {
        $this->authorize('delete', $product);

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

        $product->cartItems()->delete();
        $product->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف المنتج']);
    }

    public function updateStock(Request $request, Product $product)
    {
        $this->authorize('updateStock', $product);

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
