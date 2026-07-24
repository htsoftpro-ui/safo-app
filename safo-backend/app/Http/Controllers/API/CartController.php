<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Cart controller — manages the user's shopping cart.
 */
class CartController extends Controller
{
    /**
     * Display the user's cart with current prices.
     */
    public function index(Request $request)
    {
        $cartItems = CartItem::with(['product', 'supplier'])
            ->where('user_id', $request->user()->id)
            ->get();

        // Sync prices with current product prices
        foreach ($cartItems as $item) {
            if ($item->product) {
                $item->syncPrice();
            }
        }

        // Reload to get updated prices
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

    /**
     * Add a product to the cart.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $product = Product::with('supplier')->findOrFail($validated['product_id']);

        // Validate product availability
        $blockReason = $product->getOrderBlockReason($validated['quantity']);
        if ($blockReason) {
            return response()->json([
                'success' => false,
                'message' => $blockReason,
            ], 422);
        }

        // Check if product already in cart
        $existingItem = CartItem::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existingItem) {
            $newQuantity = $existingItem->quantity + $validated['quantity'];

            // Validate total quantity against stock
            $blockReason = $product->getOrderBlockReason($newQuantity);
            if ($blockReason) {
                return response()->json([
                    'success' => false,
                    'message' => $blockReason,
                ], 422);
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
            'quantity' => $validated['quantity'],
            'unit_price' => $product->price,
            'total_price' => $product->price * $validated['quantity'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $cartItem->load(['product', 'supplier']);

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة المنتج إلى السلة',
            'data' => new CartResource($cartItem),
        ], 201);
    }

    /**
     * Update cart item quantity.
     */
    public function update(Request $request, CartItem $cartItem)
    {
        if ($cartItem->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $product = $cartItem->product;
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'المنتج لم يعد متاحاً',
            ], 422);
        }

        $blockReason = $product->getOrderBlockReason($validated['quantity']);
        if ($blockReason) {
            return response()->json([
                'success' => false,
                'message' => $blockReason,
            ], 422);
        }

        $cartItem->update([
            'quantity' => $validated['quantity'],
            'unit_price' => $product->price,
            'total_price' => $product->price * $validated['quantity'],
        ]);

        $cartItem->load(['product', 'supplier']);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث السلة',
            'data' => new CartResource($cartItem),
        ]);
    }

    /**
     * Remove item from cart.
     */
    public function destroy(Request $request, CartItem $cartItem)
    {
        if ($cartItem->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف العنصر من السلة',
        ]);
    }

    /**
     * Clear all items from the user's cart.
     */
    public function clear(Request $request)
    {
        CartItem::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم إفراغ السلة',
        ]);
    }
}
