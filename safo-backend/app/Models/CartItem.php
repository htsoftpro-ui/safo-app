<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'user_id', 'product_id', 'supplier_id', 'quantity',
        'unit_price', 'total_price', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function user()     { return $this->belongsTo(User::class); }
    public function product()  { return $this->belongsTo(Product::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }

    /**
     * Recalculate total_price based on current quantity and unit_price.
     */
    public function recalculateTotal(): void
    {
        $this->total_price = $this->unit_price * $this->quantity;
        $this->save();
    }

    /**
     * Sync unit_price with current product price.
     * Called when viewing cart to detect price changes.
     */
    public function syncPrice(): bool
    {
        $currentPrice = $this->product->price;
        if ($this->unit_price != $currentPrice) {
            $this->unit_price = $currentPrice;
            $this->recalculateTotal();
            return true; // price changed
        }
        return false;
    }
}
