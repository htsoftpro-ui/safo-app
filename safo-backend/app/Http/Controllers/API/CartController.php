<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cartItems = CartItem::with(['product', 'supplier'])
            ->where('user_id', $request->user()->id)
            ->get();

        foreach ($cartItems as $item) {
            if ($item->product) {
                $item->syncPrice();
            }
        }

        $cartItems = CartItem::with(['product', 'supplier'])
            ->where('user_id', $request->user()->id)
            ->get();

        $subtotal = $cartItems->sum('total_price');

        return response()->json([
            'success' => true,
            'data' => [
                'items' => CartResource::collection($cartItems),
                'items_count' => $cartItems->sum('quantity'),
                'subtotal' => (float) $subtotal,
            ],
        ]);
    }

    public function store(StoreCartItemRequest $request)
    {
        $product = Product::with('supplier')->findOrFail($request->product_id);

        $blockReason = $product->getOrderBlockReason($request->quantity);
        if ($blockReason) {
            return response()->json(['success' => false, 'message' => $blockReason], 422);
        }

        $existingItem = CartItem::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existingItem) {
            $newQuantity = $existingItem->quantity + $request->quantity;
            $blockReason = $product->getOrderBlockReason($newQuantity);
            if ($blockReason) {
                return response()->json(['success' => false, 'message' => $blockReason], 422);
            }

            $existingItem->update([
                'quantity' => $newQuantity,
                'total_price' => $product->price * $newQuantity,
            ]);
            $existingItem->load(['product', 'supplier']);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث كمية المنتج في السلة',
                'data' => new CartResource($existingItem),
            ]);
        }

        $cartItem = CartItem::create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'supplier_id' => $product->supplier_id,
            'quantity' => $request->quantity,
            'unit_price' => $product->price,
            'total_price' => $product->price * $request->quantity,
            'notes' => $request->notes ?? null,
        ]);

        $cartItem->load(['product', 'supplier']);

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة المنتج إلى السلة',
            'data' => new CartResource($cartItem),
        ], 201);
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem)
    {
        $this->authorize('update', $cartItem);

        $product = $cartItem->product;
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'المنتج لم يعد متاحاً'], 422);
        }

        $blockReason = $product->getOrderBlockReason($request->quantity);
        if ($blockReason) {
            return response()->json(['success' => false, 'message' => $blockReason], 422);
        }

        $cartItem->update([
            'quantity' => $request->quantity,
            'unit_price' => $product->price,
            'total_price' => $product->price * $request->quantity,
        ]);

        $cartItem->load(['product', 'supplier']);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث السلة',
            'data' => new CartResource($cartItem),
        ]);
    }

    public function destroy(Request $request, CartItem $cartItem)
    {
        $this->authorize('delete', $cartItem);
        $cartItem->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف العنصر من السلة']);
    }

    public function clear(Request $request)
    {
        CartItem::where('user_id', $request->user()->id)->delete();
        return response()->json(['success' => true, 'message' => 'تم إفراغ السلة']);
    }
}
