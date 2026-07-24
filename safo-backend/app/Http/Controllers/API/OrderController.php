<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Order;
use App\Services\NotificationService;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private NotificationService $notificationService,
    ) {}

    public function store(StoreOrderRequest $request)
    {
        $address = Address::where('user_id', $request->user()->id)
            ->findOrFail($request->address_id);

        try {
            $order = $this->orderService->createOrder(
                $request->user(),
                $address,
                $request->payment_method,
                $request->coupon_code ?? null,
            );

            $this->notificationService->notifyOrderCreated($order);
            $this->notificationService->notifySupplierNewOrder($order);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الطلب بنجاح',
                'data' => new OrderResource($order),
            ], 201);
        } catch (\App\Exceptions\OrderCreationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function index(Request $request)
    {
        $orders = Order::with(['items', 'supplier'])
            ->forUser($request->user()->id)
            ->when($request->has('status'), fn ($q) => $q->byStatus($request->status))
            ->latest()
            ->paginate(20);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order)
    {
        $this->authorize('view', $order);
        $order->load(['items', 'supplier', 'statusHistory.changedBy']);

        return new OrderResource($order);
    }

    public function cancel(Request $request, Order $order)
    {
        $this->authorize('cancel', $order);

        $request->validate(['reason' => 'required|string|max:500']);

        try {
            $order = $this->orderService->cancelOrder(
                $order,
                $request->reason,
                $request->user()->id,
            );

            $this->notificationService->notifyOrderStatusChanged($order, 'pending');

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء الطلب',
                'data' => new OrderResource($order),
            ]);
        } catch (\App\Exceptions\OrderCreationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function confirmDelivery(Request $request, Order $order)
    {
        $this->authorize('confirmDelivery', $order);

        if ($order->status !== Order::STATUS_SHIPPED) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تأكيد الاستلام في هذه المرحلة',
            ], 422);
        }

        $oldStatus = $order->status;
        $order->transitionTo(Order::STATUS_DELIVERED, 'تأكيد الاستلام من العميل', $request->user()->id);

        $this->notificationService->notifyOrderStatusChanged($order, $oldStatus);

        return response()->json([
            'success' => true,
            'message' => 'تم تأكيد الاستلام',
            'data' => new OrderResource($order),
        ]);
    }
}
