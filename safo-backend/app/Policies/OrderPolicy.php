<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    // ─── Customer Policies ────────────────────────────────

    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->id === $order->user_id
            && $order->canBeCancelledBy('customer');
    }

    public function confirmDelivery(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }

    // ─── Supplier Policies ───────────────────────────────

    public function supplierView(User $user, Order $order): bool
    {
        return $user->supplier && $user->supplier->id === $order->supplier_id;
    }

    public function supplierAccept(User $user, Order $order): bool
    {
        return $user->supplier
            && $user->supplier->id === $order->supplier_id
            && $order->status === Order::STATUS_PENDING;
    }

    public function supplierReject(User $user, Order $order): bool
    {
        return $user->supplier
            && $user->supplier->id === $order->supplier_id
            && $order->status === Order::STATUS_PENDING;
    }

    public function supplierProcess(User $user, Order $order): bool
    {
        return $user->supplier
            && $user->supplier->id === $order->supplier_id
            && $order->status === Order::STATUS_CONFIRMED;
    }

    public function supplierReady(User $user, Order $order): bool
    {
        return $user->supplier
            && $user->supplier->id === $order->supplier_id
            && $order->status === Order::STATUS_PROCESSING;
    }

    public function supplierShip(User $user, Order $order): bool
    {
        return $user->supplier
            && $user->supplier->id === $order->supplier_id
            && $order->status === Order::STATUS_READY;
    }
}
