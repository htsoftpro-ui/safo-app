<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Notification service — handles push notifications via FCM.
 *
 * Engineering decisions:
 * - FCM HTTP v1 API (not legacy) — Google's recommended approach
 * - Notifications are queued (not blocking)
 * - Failed notifications are logged but don't break order flow
 * - In-app notifications stored in DB for persistence
 */
class NotificationService
{
    /**
     * Send order confirmation to customer.
     */
    public function notifyOrderCreated(Order $order): void
    {
        $this->sendToUser($order->user, [
            'title' => 'تم إنشاء طلبك',
            'body' => "طلبك رقم {$order->order_number} تم استلامه بنجاح",
            'data' => [
                'type' => 'order_created',
                'order_id' => $order->id,
            ],
        ]);
    }

    /**
     * Notify supplier of new order.
     */
    public function notifySupplierNewOrder(Order $order): void
    {
        $supplierUser = $order->supplier->user;
        if (!$supplierUser) return;

        $this->sendToUser($supplierUser, [
            'title' => 'طلب جديد',
            'body' => "لديك طلب جديد رقم {$order->order_number}",
            'data' => [
                'type' => 'new_order',
                'order_id' => $order->id,
            ],
        ]);
    }

    /**
     * Notify customer of order status change.
     */
    public function notifyOrderStatusChanged(Order $order, string $oldStatus): void
    {
        $statusMessages = [
            'confirmed' => 'تم تأكيد طلبك',
            'processing' => 'طلبك قيد التجهيز',
            'ready' => 'طلبك جاهز للشحن',
            'shipped' => 'تم شحن طلبك',
            'delivered' => 'تم توصيل طلبك بنجاح',
            'cancelled' => 'تم إلغاء طلبك',
        ];

        $message = $statusMessages[$order->status] ?? 'تم تحديث حالة طلبك';

        $this->sendToUser($order->user, [
            'title' => 'تحديث الطلب',
            'body' => "{$message} — {$order->order_number}",
            'data' => [
                'type' => 'order_status_changed',
                'order_id' => $order->id,
                'status' => $order->status,
            ],
        ]);
    }

    /**
     * Send push notification to a specific user.
     */
    private function sendToUser(User $user, array $notification): void
    {
        // Store in-app notification
        $user->notifications()->create([
            'type' => $notification['data']['type'],
            'title' => $notification['title'],
            'message' => $notification['body'],
            'data' => $notification['data'],
        ]);

        // Send FCM push notification
        if (empty($user->fcm_token)) {
            return;
        }

        try {
            $this->sendFCM($user->fcm_token, $notification);
        } catch (\Exception $e) {
            // Log but don't throw — notification failure shouldn't break order flow
            Log::warning('FCM notification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send via Firebase Cloud Messaging HTTP v1 API.
     */
    private function sendFCM(string $token, array $notification): void
    {
        $projectId = config('services.firebase.project_id');
        $credentialsPath = config('services.firebase.credentials_path');

        if (!$projectId || !$credentialsPath) {
            Log::debug('Firebase not configured, skipping push notification');
            return;
        }

        // TODO: implement actual FCM HTTP v1 API call with service account
        // For MVP, we log the notification instead
        Log::info('FCM notification', [
            'token' => substr($token, 0, 20) . '...',
            'title' => $notification['title'],
            'body' => $notification['body'],
        ]);
    }
}
