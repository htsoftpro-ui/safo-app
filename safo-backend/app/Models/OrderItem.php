<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Order item — snapshot of product at order time.
 * product_name, product_image, product_unit are copied from Product
 * so the order record survives product changes or deletion.
 */
class OrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'product_image',
        'product_unit', 'quantity', 'unit_price', 'total_price', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function order()   { return $this->belongsTo(Order::class); }
    public function product() { return $this->belongsTo(Product::class); }

    /**
     * Create from a Product model, snapshotting its current state.
     */
    public static function fromProduct(Product $product, int $quantity, ?string $notes = null): array
    {
        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_image' => $product->thumbnail,
            'product_unit' => $product->unit,
            'quantity' => $quantity,
            'unit_price' => $product->price,
            'total_price' => $product->price * $quantity,
            'notes' => $notes,
        ];
    }
}
