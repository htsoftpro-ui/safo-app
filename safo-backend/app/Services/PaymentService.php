<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Supplier;
use App\Models\User;

/**
 * Payment Service — handles payment processing.
 *
 * Supports:
 * - Cash on Delivery (cod)
 * - Credit/Deferred payment (credit)
 * - Online payment gateway (placeholder for future integration)
 */
class PaymentService
{
    /**
     * Process payment for an order.
     */
    public function processPayment(Order $order, string $method): array
    {
        return match ($method) {
            'cash' => $this->processCash($order),
            'credit' => $this->processCredit($order),
            'wallet' => $this->processWallet($order),
            default => ['success' => false, 'message' => 'طريقة دفع غير مدعومة'],
        };
    }

    /**
     * Cash on Delivery — mark as pending, paid on delivery.
     */
    private function processCash(Order $order): array
    {
        $order->update([
            'payment_method' => 'cash',
            'payment_status' => 'pending',
        ]);

        return [
            'success' => true,
            'message' => 'الدفع نقدي عند التوصيل',
            'payment_method' => 'cash',
        ];
    }

    /**
     * Credit/Deferred payment — requires supplier credit limit.
     */
    private function processCredit(Order $order): array
    {
        $supplier = Supplier::find($order->supplier_id);
        $customer = User::find($order->user_id);

        if (!$supplier) {
            return ['success' => false, 'message' => 'المورد غير موجود'];
        }

        // Check if supplier allows credit
        $paymentMethods = $supplier->payment_methods ?? ['cash'];
        if (!in_array('credit', $paymentMethods)) {
            return ['success' => false, 'message' => 'المورد لا يدعم الدفع بالآجل'];
        }

        // Check customer credit limit (if applicable)
        $creditLimit = $customer->credit_limit ?? 0;
        $creditUsed = $customer->credit_used ?? 0;

        if ($creditLimit > 0 && ($creditUsed + $order->total_amount) > $creditLimit) {
            return ['success' => false, 'message' => 'تم تجاوز حد האשראי المسموح'];
        }

        $order->update([
            'payment_method' => 'credit',
            'payment_status' => 'pending',
        ]);

        // Update customer credit usage
        if ($creditLimit > 0) {
            $customer->increment('credit_used', $order->total_amount);
        }

        return [
            'success' => true,
            'message' => 'تم تأكيد الدفع بالآجل',
            'payment_method' => 'credit',
            'due_date' => now()->addDays(30)->toDateString(),
        ];
    }

    /**
     * Wallet payment (placeholder).
     */
    private function processWallet(Order $order): array
    {
        // TODO: Implement wallet system
        return ['success' => false, 'message' => 'المحفظة الإلكترونية غير متاحة حالياً'];
    }

    /**
     * Mark order as paid (called on delivery confirmation).
     */
    public function markAsPaid(Order $order): void
    {
        $order->update(['payment_status' => 'paid']);
    }

    /**
     * Process refund (called on cancellation/return).
     */
    public function processRefund(Order $order): array
    {
        if ($order->payment_status !== 'paid') {
            return ['success' => false, 'message' => 'الطلب غير مدفوع'];
        }

        $order->update(['payment_status' => 'refunded']);

        // Restore credit if credit payment
        if ($order->payment_method === 'credit') {
            $customer = User::find($order->user_id);
            if ($customer) {
                $customer->decrement('credit_used', $order->total_amount);
            }
        }

        return [
            'success' => true,
            'message' => 'تم استرداد المبلغ',
        ];
    }
}
