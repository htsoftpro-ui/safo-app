<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Supplier;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class SupplierOrderController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    private function getSupplier(Request $request): Supplier
    {
        return Supplier::where('user_id', $request->user()->id)->firstOrFail();
    }

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

    public function show(Request $request, Order $order)
    {
        $this->authorize('supplierView', $order);
        $order->load(['items', 'user', 'statusHistory.changedBy']);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ]);
    }

    public function accept(Request $request, Order $order)
    {
        $this->authorize('supplierAccept', $order);

        $oldStatus = $order->status;
        $order->transitionTo(Order::STATUS_CONFIRMED, 'تم قبول الطلب من المورد', $request->user()->id);
        $this->notificationService->notifyOrderStatusChanged($order, $oldStatus);

        return response()->json([
            'success' => true,
            'message' => 'تم قبول الطلب',
            'data' => new OrderResource($order->fresh()),
        ]);
    }

    public function reject(Request $request, Order $order)
    {
        $this->authorize('supplierReject', $order);

        $request->validate(['reason' => 'required|string|max:500']);

        foreach ($order->items as $item) {
            \App\Models\Product::where('id', $item->product_id)
                ->increment('stock_quantity', $item->quantity);
        }

        $oldStatus = $order->status;
        $order->cancellation_reason = $request->reason;
        $order->cancelled_by = 'supplier';
        $order->save();
        $order->transitionTo(Order::STATUS_CANCELLED, $request->reason, $request->user()->id);
        $this->notificationService->notifyOrderStatusChanged($order, $oldStatus);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض الطلب',
            'data' => new OrderResource($order->fresh()),
        ]);
    }

    public function process(Request $request, Order $order)
    {
        $this->authorize('supplierProcess', $order);

        $oldStatus = $order->status;
        $order->transitionTo(Order::STATUS_PROCESSING, 'جاري تحضير الطلب', $request->user()->id);
        $this->notificationService->notifyOrderStatusChanged($order, $oldStatus);

        return response()->json([
            'success' => true,
            'message' => 'تم بدء تحضير الطلب',
            'data' => new OrderResource($order->fresh()),
        ]);
    }

    public function ready(Request $request, Order $order)
    {
        $this->authorize('supplierReady', $order);

        $oldStatus = $order->status;
        $order->transitionTo(Order::STATUS_READY, 'الطلب جاهز للشحن', $request->user()->id);
        $this->notificationService->notifyOrderStatusChanged($order, $oldStatus);

        return response()->json([
            'success' => true,
            'message' => 'الطلب جاهز للشحن',
            'data' => new OrderResource($order->fresh()),
        ]);
    }

    public function ship(Request $request, Order $order)
    {
        $this->authorize('supplierShip', $order);

        $request->validate(['notes' => 'nullable|string|max:500']);

        $order->delivery_notes = $request->notes ?? $order->delivery_notes;
        $order->save();

        $oldStatus = $order->status;
        $order->transitionTo(Order::STATUS_SHIPPED, 'تم شحن الطلب', $request->user()->id);
        $this->notificationService->notifyOrderStatusChanged($order, $oldStatus);

        return response()->json([
            'success' => true,
            'message' => 'تم شحن الطلب',
            'data' => new OrderResource($order->fresh()),
        ]);
    }
}
