<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Order model.
 *
 * Engineering decisions:
 * - order_number is human-readable (ORD-20260725-001) not just auto-increment
 * - delivery_address is a TEXT snapshot, not just FK — preserves history
 * - Status transitions are enforced via canTransitionTo()
 * - Financial fields use decimal(10,2) for Yemeni Rial precision
 * - confirmed_at, shipped_at, delivered_at are explicit timestamps for analytics
 */
class Order extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PROCESSING = 'processing';
    const STATUS_READY = 'ready';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    const PAYMENT_METHOD_CASH = 'cash';
    const PAYMENT_METHOD_CREDIT = 'credit';
    const PAYMENT_METHOD_WALLET = 'wallet';

    const PAYMENT_PENDING = 'pending';
    const PAYMENT_PAID = 'paid';
    const PAYMENT_FAILED = 'failed';
    const PAYMENT_REFUNDED = 'refunded';

    /**
     * Valid status transitions.
     * This is the single source of truth for order lifecycle.
     */
    const VALID_TRANSITIONS = [
        self::STATUS_PENDING    => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
        self::STATUS_CONFIRMED  => [self::STATUS_PROCESSING, self::STATUS_CANCELLED],
        self::STATUS_PROCESSING => [self::STATUS_READY, self::STATUS_CANCELLED],
        self::STATUS_READY      => [self::STATUS_SHIPPED],
        self::STATUS_SHIPPED    => [self::STATUS_DELIVERED],
        self::STATUS_DELIVERED  => [], // terminal state
        self::STATUS_CANCELLED  => [], // terminal state
    ];

    protected $fillable = [
        'order_number',
        'user_id',
        'supplier_id',
        'address_id',
        'status',
        'subtotal',
        'delivery_fee',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'payment_method',
        'payment_status',
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_notes',
        'estimated_delivery_at',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancellation_reason',
        'cancelled_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'delivery_latitude' => 'decimal:8',
            'delivery_longitude' => 'decimal:8',
            'estimated_delivery_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    // ─── Relationships ─────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at');
    }

    // ─── Scopes ────────────────────────────────────────────

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }

    // ─── Business Logic ────────────────────────────────────

    /**
     * Check if this order can transition to the given status.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::VALID_TRANSITIONS[$this->status] ?? []);
    }

    /**
     * Transition to a new status with validation and audit trail.
     *
     * @throws \InvalidArgumentException if transition is invalid
     */
    public function transitionTo(string $newStatus, ?string $note = null, ?int $changedBy = null): self
    {
        if (!$this->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from '{$this->status}' to '{$newStatus}'"
            );
        }

        $oldStatus = $this->status;

        // Update order
        $this->status = $newStatus;

        // Set explicit timestamps
        $timestampFields = [
            self::STATUS_CONFIRMED => 'confirmed_at',
            self::STATUS_SHIPPED => 'shipped_at',
            self::STATUS_DELIVERED => 'delivered_at',
        ];
        if (isset($timestampFields[$newStatus])) {
            $this->{$timestampFields[$newStatus]} = now();
        }

        // Handle cancellation
        if ($newStatus === self::STATUS_CANCELLED) {
            $this->cancelled_by = $changedBy ? User::find($changedBy)?->type : null;
        }

        // Auto-mark as paid on delivery (cash payment)
        if ($newStatus === self::STATUS_DELIVERED && $this->payment_method === self::PAYMENT_METHOD_CASH) {
            $this->payment_status = self::PAYMENT_PAID;
        }

        $this->save();

        // Record history
        OrderStatusHistory::create([
            'order_id' => $this->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'note' => $note,
            'changed_by' => $changedBy,
        ]);

        return $this;
    }

    /**
     * Generate a unique, human-readable order number.
     * Format: ORD-YYYYMMDD-NNN
     */
    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $sequence = self::whereDate('created_at', today())->count() + 1;
        return "ORD-{$date}-" . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Snapshot the delivery address from the user's address record.
     */
    public function snapshotAddress(Address $address): void
    {
        $this->address_id = $address->id;
        $this->delivery_address = $address->full_address;
        $this->delivery_latitude = $address->latitude;
        $this->delivery_longitude = $address->longitude;
        $this->save();
    }

    /**
     * Get items count for display.
     */
    public function getItemsCountAttribute(): int
    {
        return $this->items()->sum('quantity');
    }

    /**
     * Check if order can be cancelled by a given user type.
     */
    public function canBeCancelledBy(string $userType): bool
    {
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED])) {
            return false;
        }

        // Customers can cancel pending/confirmed orders
        if ($userType === 'customer') {
            return true;
        }

        // Suppliers can cancel confirmed orders
        if ($userType === 'supplier' && $this->status === self::STATUS_CONFIRMED) {
            return true;
        }

        // Admins can always cancel
        if ($userType === 'admin') {
            return true;
        }

        return false;
    }
}
