<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'subtotal' => (float) $this->subtotal,
            'delivery_fee' => (float) $this->delivery_fee,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount' => (float) $this->total_amount,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'delivery_address' => $this->delivery_address,
            'delivery_notes' => $this->delivery_notes,
            'items_count' => $this->items->sum('quantity'),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'status_history' => StatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'shipped_at' => $this->shipped_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }

    private function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'قيد المراجعة',
            'confirmed' => 'مؤكد',
            'processing' => 'قيد التجهيز',
            'ready' => 'جاهز للشحن',
            'shipped' => 'تم الشحن',
            'delivered' => 'تم التوصيل',
            'cancelled' => 'ملغي',
            default => $this->status,
        };
    }
}
