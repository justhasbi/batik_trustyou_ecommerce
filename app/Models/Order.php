<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'order_number',
        'subtotal',
        'shipping_cost',
        'total',
        'status',
        'shipping_status',
        'recipient_name',
        'recipient_phone',
        'shipping_address',
        'courier',
        'tracking_number',
    ];
 
    protected $casts = [
        'subtotal'      => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total'         => 'decimal:2',
    ];
 
    public function user()
    {
        return $this->belongsTo(User::class);
    }
 
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
