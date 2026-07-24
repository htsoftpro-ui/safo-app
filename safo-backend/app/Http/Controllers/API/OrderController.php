<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
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

    /**
     * Create order from cart.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required|in:cash,credit,wallet',
            'coupon_code' => 'nullable|string',
        ]);

        $address = Address::where('user_id', $request->user()->id)
            ->findOrFail($validated['address_id']);

        try {
            $order = $this->orderService->createOrder(
                $request->user(),
                $address,
                $validated['payment_method'],
                $validated['coupon_code'] ?? null,
            );

            // Notifications (after successful creation)
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

    /**
     * List user's orders.
     */
    public function index(Request $request)
    {
        $orders = Order::with(['items', 'supplier'])
            ->forUser($request->user()->id)
            ->when($request->has('status'), fn ($q) => $q->byStatus($request->status))
            ->latest()
            ->paginate(20);

        return OrderResource::collection($orders);
    }

    /**
     * Show single order.
     */
    public function show(Request $request, Order $order)
    {
        // Authorization: user can only see their own orders
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح',
            ], 403);
        }

        $order->load(['items', 'supplier', 'statusHistory.changedBy']);

        return new OrderResource($order);
    }

    /**
     * Cancel order.
     */
    public function cancel(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $order = $this->orderService->cancelOrder(
                $order,
                $validated['reason'],
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

    /**
     * Confirm delivery.
     */
    public function confirmDelivery(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

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
