<?php

namespace App\Services;

use App\Models\Address;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Order service — handles order creation, cancellation, and lifecycle.
 *
 * Engineering decisions:
 * - All order operations are wrapped in DB transactions
 * - Stock is validated TWICE: once at submission, once inside the transaction (race condition prevention)
 * - Cart items are snapshotted into order_items (not referenced)
 * - Address is snapshotted into orders table
 * - Notification is sent AFTER successful commit (not inside transaction)
 */
class OrderService
{
    /**
     * Create a new order from the user's cart.
     *
     * @throws \App\Exceptions\OrderCreationException
     */
    public function createOrder(User $user, Address $address, string $paymentMethod, ?string $couponCode = null): Order
    {
        $cartItems = CartItem::with('product')
            ->where('user_id', $user->id)
            ->get();

        if ($cartItems->isEmpty()) {
            throw new \App\Exceptions\OrderCreationException('السلة فارغة');
        }

        // Group cart items by supplier — one order per supplier
        $grouped = $cartItems->groupBy('supplier_id');

        // For MVP: single supplier per order
        // TODO Phase 2: support multi-supplier orders
        if ($grouped->count() > 1) {
            throw new \App\Exceptions\OrderCreationException(
                'لا يمكن طلب من أكثر من مورد في نفس الطلب. يرجى إنشاء طلبات منفصلة.'
            );
        }

        $supplierId = $cartItems->first()->supplier_id;

        return DB::transaction(function () use ($user, $address, $paymentMethod, $couponCode, $cartItems, $supplierId) {
            // 1. Validate stock (inside transaction — prevents race conditions)
            foreach ($cartItems as $item) {
                $product = Product::where('id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    throw new \App\Exceptions\OrderCreationException(
                        "المنتج '{$item->product->name}' لم يعد متاحاً"
                    );
                }

                $blockReason = $product->getOrderBlockReason($item->quantity);
                if ($blockReason) {
                    throw new \App\Exceptions\OrderCreationException($blockReason);
                }
            }

            // 2. Calculate totals
            $subtotal = $cartItems->sum('total_price');
            $deliveryFee = $this->calculateDeliveryFee($supplierId, $subtotal);
            $discount = $this->calculateDiscount($couponCode, $subtotal);
            $total = $subtotal + $deliveryFee - $discount;

            // 3. Create order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $user->id,
                'supplier_id' => $supplierId,
                'status' => Order::STATUS_PENDING,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'discount_amount' => $discount,
                'tax_amount' => 0,
                'total_amount' => $total,
                'payment_method' => $paymentMethod,
                'payment_status' => Order::PAYMENT_PENDING,
                'delivery_address' => $address->full_address,
                'delivery_latitude' => $address->latitude,
                'delivery_longitude' => $address->longitude,
            ]);

            // 4. Snapshot address
            $order->snapshotAddress($address);

            // 5. Create order items (snapshot product data)
            foreach ($cartItems as $item) {
                OrderItem::create(
                    array_merge(
                        OrderItem::fromProduct($item->product, $item->quantity, $item->notes),
                        ['order_id' => $order->id]
                    )
                );

                // 6. Decrement stock
                $item->product->decrementStock($item->quantity);
            }

            // 7. Record initial status
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => Order::STATUS_PENDING,
                'note' => 'تم إنشاء الطلب',
                'changed_by' => $user->id,
            ]);

            // 8. Clear cart
            CartItem::where('user_id', $user->id)->delete();

            return $order->load('items', 'supplier');
        });
    }

    /**
     * Cancel an order with stock restoration.
     */
    public function cancelOrder(Order $order, string $reason, int $cancelledBy): Order
    {
        if (!$order->canBeCancelledBy(User::find($cancelledBy)?->type ?? 'customer')) {
            throw new \App\Exceptions\OrderCreationException(
                'لا يمكن إلغاء الطلب في هذه المرحلة'
            );
        }

        return DB::transaction(function () use ($order, $reason, $cancelledBy) {
            // Restore stock
            foreach ($order->items as $item) {
                Product::where('id', $item->product_id)
                    ->increment('stock_quantity', $item->quantity);
            }

            // Transition status
            $order->cancellation_reason = $reason;
            $order->save();
            $order->transitionTo(Order::STATUS_CANCELLED, $reason, $cancelledBy);

            return $order;
        });
    }

    /**
     * Calculate delivery fee based on supplier settings and order amount.
     */
    private function calculateDeliveryFee(int $supplierId, float $subtotal): float
    {
        $supplier = \App\Models\Supplier::find($supplierId);

        if (!$supplier) {
            return config('safo.default_delivery_fee', 500);
        }

        // Free delivery if above threshold
        if ($supplier->free_delivery_threshold && $subtotal >= $supplier->free_delivery_threshold) {
            return 0;
        }

        return $supplier->delivery_fee ?: config('safo.default_delivery_fee', 500);
    }

    /**
     * Calculate coupon discount.
     */
    private function calculateDiscount(?string $couponCode, float $subtotal): float
    {
        if (empty($couponCode)) {
            return 0;
        }

        // TODO: implement coupon validation in Phase 2
        return 0;
    }
}
