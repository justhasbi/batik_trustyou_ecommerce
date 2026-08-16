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
        'shipping_method',
        'tracking_number',
        'payment_method',
        'payment_channel',
        'transaction_code',
        'va_number',
        'qr_payload',
        'payment_expires_at',
        'paid_at',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'subtotal'           => 'decimal:2',
        'shipping_cost'      => 'decimal:2',
        'total'              => 'decimal:2',
        'payment_expires_at' => 'datetime',
        'paid_at'            => 'datetime',
        'shipped_at'         => 'datetime',
        'delivered_at'       => 'datetime',
    ];

    // Urutan tahapan pengiriman (dipakai untuk simulasi & timeline)
    public const SHIPPING_FLOW = ['not_shipped', 'packed', 'shipped', 'in_transit', 'delivered'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['paid', 'processing', 'completed'], true);
    }

    public function isPaymentExpired(): bool
    {
        return $this->payment_expires_at !== null
            && $this->status === 'pending'
            && $this->payment_expires_at->isPast();
    }
}
