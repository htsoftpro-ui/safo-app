<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Supplier;
use App\Services\NotificationService;
use Illuminate\Http\Request;

/**
 * Supplier order controller — manage incoming orders for suppliers.
 */
class SupplierOrderController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    /**
     * Get the authenticated supplier's record.
     */
    private function getSupplier(Request $request): Supplier
    {
        return Supplier::where('user_id', $request->user()->id)->firstOrFail();
    }

    /**
     * List orders for the supplier with optional status filter.
     */
    public function index(Request $request)
    {
        $supplier = $this->getSupplier($request);

        $request->validate([
            'status' => 'nullable|in:pending,confirmed,processing,ready,shipped,delivered,cancelled,returned',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $orders = Order::with(['items', 'user'])
            ->forSupplier($supplier->id)
            ->when($request->has('status'), fn ($q) => $q->byStatus($request->status))
            ->latest()
            ->paginate($request->input('per_page', 20));

        return OrderResource::collection($orders);
    }

    /**
     * Show single order details.
     */
    public function show(Request $request, Order $order)
    {
        $supplier = $this->getSupplier($request);

        if ($order->supplier_id !== $supplier->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $order->load(['items', 'user', 'statusHistory.changedBy']);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Accept order (pending → confirmed).
     */
    public function accept(Request $request, Order $order)
    {
        $supplier = $this->getSupplier($request);

        if ($order->supplier_id !== $supplier->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        if ($order->status !== Order::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن قبول الطلب في هذه المرحلة',
            ], 422);
        }

        $oldStatus = $order->status;
        $order->transitionTo(Order::STATUS_CONFIRMED, 'تم قبول الطلب من المورد', $request->user()->id);

        $this->notificationService->notifyOrderStatusChanged($order, $oldStatus);

        return response()->json([
            'success' => true,
            'message' => 'تم قبول الطلب',
            'data' => new OrderResource($order->fresh()),
        ]);
    }

    /**
     * Reject order (pending → cancelled with reason).
     */
    public function reject(Request $request, Order $order)
    {
        $supplier = $this->getSupplier($request);

        if ($order->supplier_id !== $supplier->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        if ($order->status !== Order::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن رفض الطلب في هذه المرحلة',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Restore stock
        foreach ($order->items as $item) {
            \App\Models\Product::where('id', $item->product_id)
                ->increment('stock_quantity', $item->quantity);
        }

        $oldStatus = $order->status;
        $order->cancellation_reason = $validated['reason'];
        $order->cancelled_by = 'supplier';
        $order->save();
        $order->transitionTo(Order::STATUS_CANCELLED, $validated['reason'], $request->user()->id);

        $this->notificationService->notifyOrderStatusChanged($order, $oldStatus);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض الطلب',
            'data' => new OrderResource($order->fresh()),
        ]);
    }

    /**
     * Process order (confirmed → processing).
     */
    public function process(Request $request, Order $order)
    {
        $supplier = $this->getSupplier($request);

        if ($order->supplier_id !== $supplier->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        if ($order->status !== Order::STATUS_CONFIRMED) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تحضير الطلب في هذه المرحلة',
            ], 422);
        }

        $oldStatus = $order->status;
        $order->transitionTo(Order::STATUS_PROCESSING, 'جاري تحضير الطلب', $request->user()->id);

        $this->notificationService->notifyOrderStatusChanged($order, $oldStatus);

        return response()->json([
            'success' => true,
            'message' => 'تم بدء تحضير الطلب',
            'data' => new OrderResource($order->fresh()),
        ]);
    }

    /**
     * Mark order as ready (processing → ready).
     */
    public function ready(Request $request, Order $order)
    {
        $supplier = $this->getSupplier($request);

        if ($order->supplier_id !== $supplier->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        if ($order->status !== Order::STATUS_PROCESSING) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تحديد الطلب كجاهز في هذه المرحلة',
            ], 422);
        }

        $oldStatus = $order->status;
        $order->transitionTo(Order::STATUS_READY, 'الطلب جاهز للشحن', $request->user()->id);

        $this->notificationService->notifyOrderStatusChanged($order, $oldStatus);

        return response()->json([
            'success' => true,
            'message' => 'الطلب جاهز للشحن',
            'data' => new OrderResource($order->fresh()),
        ]);
    }

    /**
     * Ship order (ready → shipped).
     */
    public function ship(Request $request, Order $order)
    {
        $supplier = $this->getSupplier($request);

        if ($order->supplier_id !== $supplier->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        if ($order->status !== Order::STATUS_READY) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن شحن الطلب في هذه المرحلة',
            ], 422);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $oldStatus = $order->status;
        $order->delivery_notes = $validated['notes'] ?? $order->delivery_notes;
        $order->save();
        $order->transitionTo(Order::STATUS_SHIPPED, 'تم شحن الطلب', $request->user()->id);

        $this->notificationService->notifyOrderStatusChanged($order, $oldStatus);

        return response()->json([
            'success' => true,
            'message' => 'تم شحن الطلب',
            'data' => new OrderResource($order->fresh()),
        ]);
    }
}
