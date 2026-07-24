<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id', 'title', 'address', 'city', 'area', 'building',
        'floor', 'apartment', 'landmark', 'latitude', 'longitude', 'is_default',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_default' => 'boolean',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }

    public function scopeDefault($query) { return $query->where('is_default', true); }

    /**
     * Full address string for display and order snapshot.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->area,
            $this->city,
            $this->building ? "بناية {$this->building}" : null,
            $this->floor ? "طابق {$this->floor}" : null,
            $this->apartment ? "شقة {$this->apartment}" : null,
        ]);
        return implode('، ', $parts);
    }

    /**
     * When setting as default, unset any existing default for this user.
     */
    protected static function booted(): void
    {
        static::saving(function (Address $address) {
            if ($address->is_default) {
                static::where('user_id', $address->user_id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}
