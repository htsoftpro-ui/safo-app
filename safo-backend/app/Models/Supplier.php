<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'company_name', 'company_name_en', 'commercial_register',
        'tax_number', 'logo', 'banner', 'description', 'min_order_amount',
        'delivery_fee', 'free_delivery_threshold', 'delivery_time_hours',
        'is_verified', 'is_active', 'working_hours', 'delivery_areas',
        'payment_methods',
    ];

    protected function casts(): array
    {
        return [
            'min_order_amount' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'free_delivery_threshold' => 'decimal:2',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'working_hours' => 'array',
            'delivery_areas' => 'array',
            'payment_methods' => 'array',
        ];
    }

    public function user()     { return $this->belongsTo(User::class); }
    public function products() { return $this->hasMany(Product::class); }
    public function orders()   { return $this->hasMany(Order::class); }

    public function scopeVerified($query) { return $query->where('is_verified', true); }
    public function scopeActive($query)   { return $query->where('is_active', true); }

    public function recalculateRating(): void
    {
        $avg = $this->orders()
            ->where('status', 'delivered')
            ->join('reviews', 'orders.id', '=', 'reviews.order_id')
            ->avg('reviews.rating');

        $this->update([
            'rating' => round($avg, 2) ?: 0,
            'total_ratings' => $this->products()->sum('total_ratings'),
        ]);
    }
}
