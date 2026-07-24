<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id', 'from_status', 'to_status', 'note', 'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    protected $dates = ['created_at'];

    public function order()     { return $this->belongsTo(Order::class); }
    public function changedBy() { return $this->belongsTo(User::class, 'changed_by'); }
}
