<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function index(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:pending,confirmed,processing,ready,shipped,delivered,cancelled,returned',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $orders = Order::with(['user', 'supplier', 'items'])
            ->when($request->has('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->has('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->has('search'), fn ($q) => $q->where('order_number', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate($request->input('per_page', 20));

        return OrderResource::collection($orders);
    }

    public function show(Order $order)
    {
        $order->load(['user', 'supplier', 'items', 'statusHistory.changedBy', 'address']);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ]);
    }

    public function cancel(Request $request, Order $order)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        if (!$order->canBeCancelledBy('admin')) {
            return response()->json(['success' => false, 'message' => 'لا يمكن إلغاء الطلب في هذه المرحلة'], 422);
        }

        foreach ($order->items as $item) {
            \App\Models\Product::where('id', $item->product_id)->increment('stock_quantity', $item->quantity);
        }

        $oldStatus = $order->status;
        $order->cancellation_reason = $request->reason;
        $order->cancelled_by = 'admin';
        $order->save();
        $order->transitionTo(Order::STATUS_CANCELLED, $request->reason, $request->user()->id);

        $this->notificationService->notifyOrderStatusChanged($order, $oldStatus);

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الطلب',
            'data' => new OrderResource($order->fresh()),
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:confirmed,processing,ready,shipped,delivered,returned',
            'note' => 'nullable|string|max:500',
        ]);

        if (!$order->canTransitionTo($request->status)) {
            return response()->json([
                'success' => false,
                'message' => "لا يمكن تغيير الحالة من {$order->status} إلى {$request->status}",
            ], 422);
        }

        $oldStatus = $order->status;
        $order->transitionTo($request->status, $request->note, $request->user()->id);
        $this->notificationService->notifyOrderStatusChanged($order, $oldStatus);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الطلب',
            'data' => new OrderResource($order->fresh()),
        ]);
    }
}
