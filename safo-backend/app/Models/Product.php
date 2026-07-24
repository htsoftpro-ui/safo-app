<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Product model.
 *
 * Engineering decisions:
 * - slug auto-generated from name (SEO + URL-friendly)
 * - images stored as JSON array (MySQL 8.0 JSON support)
 * - stock_quantity uses unsigned int (no negative stock)
 * - rating is denormalized (avg calculated on review create/update)
 * - SoftDeletes: products are archived, never hard-deleted
 */
class Product extends Model
{
    use HasFactory, HasSlug, SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'category_id',
        'name',
        'name_en',
        'slug',
        'description',
        'description_en',
        'sku',
        'barcode',
        'price',
        'compare_price',
        'cost_price',
        'unit',
        'unit_quantity',
        'min_order_quantity',
        'stock_quantity',
        'low_stock_threshold',
        'weight',
        'weight_unit',
        'images',
        'thumbnail',
        'is_active',
        'is_featured',
        'tags',
        'attributes',
        'expiry_date',
        'manufacturer',
        'country_of_origin',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'images' => 'array',
            'tags' => 'array',
            'attributes' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'expiry_date' => 'date',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    // ─── Relationships ─────────────────────────────────────

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // ─── Scopes ────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('name_en', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('barcode', $term)
              ->orWhere('sku', $term);
        });
    }

    public function scopePriceRange($query, ?float $min, ?float $max)
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price', '<=', $max);
        }
        return $query;
    }

    public function scopeSorted($query, string $sort = 'created_at', string $order = 'desc')
    {
        $allowed = ['price', 'rating', 'sales_count', 'created_at', 'name'];
        if (in_array($sort, $allowed)) {
            return $query->orderBy($sort, $order);
        }
        return $query->orderBy('created_at', 'desc');
    }

    // ─── Accessors ─────────────────────────────────────────

    public function getDiscountPercentAttribute(): int
    {
        if (!$this->compare_price || $this->compare_price <= $this->price) {
            return 0;
        }
        return (int) round((($this->compare_price - $this->price) / $this->compare_price) * 100);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->stock_quantity <= 0;
    }

    // ─── Business Logic ────────────────────────────────────

    /**
     * Check if a given quantity can be ordered.
     * Validates both stock and minimum order quantity.
     */
    public function canOrder(int $quantity): bool
    {
        return $quantity >= $this->min_order_quantity
            && $quantity <= $this->stock_quantity
            && $this->is_active;
    }

    /**
     * Get the reason why a product cannot be ordered.
     */
    public function getOrderBlockReason(?int $quantity = null): ?string
    {
        if (!$this->is_active) {
            return 'هذا المنتج لم يعد متاحاً';
        }
        if ($this->stock_quantity <= 0) {
            return 'هذا المنتج غير متوفر حالياً';
        }
        if ($quantity !== null && $quantity < $this->min_order_quantity) {
            return "الحد الأدنى للطلب هو {$this->min_order_quantity} {$this->unit}";
        }
        if ($quantity !== null && $quantity > $this->stock_quantity) {
            return "الكمية المتوفرة هي {$this->stock_quantity} {$this->unit}";
        }
        return null;
    }

    /**
     * Decrement stock safely. Returns false if insufficient.
     */
    public function decrementStock(int $quantity): bool
    {
        if ($this->stock_quantity < $quantity) {
            return false;
        }

        $this->decrement('stock_quantity', $quantity);
        $this->increment('sales_count', $quantity);
        return true;
    }

    /**
     * Recalculate average rating from reviews.
     */
    public function recalculateRating(): void
    {
        $this->update([
            'rating' => round($this->reviews()->avg('rating'), 2) ?: 0,
            'total_ratings' => $this->reviews()->count(),
        ]);
    }
}
