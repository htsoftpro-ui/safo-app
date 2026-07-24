<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User model.
 *
 * Engineering decisions:
 * - Extends Authenticatable (not Model) for Sanctum compatibility
 * - SoftDeletes: users are never hard-deleted, only marked deleted
 * - phone is the primary identifier (not email) — Yemeni market context
 * - type ENUM instead of separate roles table: simpler for 3 fixed roles
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'type',
        'store_name',
        'store_type',
        'avatar',
        'city',
        'area',
        'address',
        'otp_code',
        'otp_expires_at',
        'fcm_token',
        'is_verified',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'otp_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ─────────────────────────────────────

    public function supplier()
    {
        return $this->hasOne(Supplier::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // ─── Scopes ────────────────────────────────────────────

    public function scopeCustomers($query)
    {
        return $query->where('type', 'customer');
    }

    public function scopeSuppliers($query)
    {
        return $query->where('type', 'supplier');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Helpers ───────────────────────────────────────────

    public function isCustomer(): bool
    {
        return $this->type === 'customer';
    }

    public function isSupplier(): bool
    {
        return $this->type === 'supplier';
    }

    public function isAdmin(): bool
    {
        return $this->type === 'admin';
    }

    public function isSupplierVerified(): bool
    {
        return $this->isSupplier() && $this->supplier?->is_verified === true;
    }
}
