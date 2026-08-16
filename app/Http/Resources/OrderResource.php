<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    private const STATUS_LABELS = [
        'pending'    => 'Menunggu pembayaran',
        'paid'       => 'Dibayar',
        'processing' => 'Diproses',
        'completed'  => 'Selesai',
        'cancelled'  => 'Dibatalkan',
    ];

    private const SHIPPING_LABELS = [
        'not_shipped' => 'Belum dikirim',
        'packed'      => 'Dikemas',
        'shipped'     => 'Dikirim',
        'in_transit'  => 'Dalam perjalanan',
        'delivered'   => 'Diterima',
    ];

    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'order_number'       => $this->order_number,
            'status'             => $this->status,
            'status_label'       => self::STATUS_LABELS[$this->status] ?? $this->status,
            'shipping_status'    => $this->shipping_status,
            'shipping_label'     => self::SHIPPING_LABELS[$this->shipping_status] ?? $this->shipping_status,
            'subtotal'           => (float) $this->subtotal,
            'shipping_cost'      => (float) $this->shipping_cost,
            'total'              => (float) $this->total,

            // Penerima & pengiriman
            'recipient_name'     => $this->recipient_name,
            'recipient_phone'    => $this->recipient_phone,
            'shipping_address'   => $this->shipping_address,
            'courier'            => $this->courier,
            'shipping_method'    => $this->shipping_method,
            'tracking_number'    => $this->tracking_number,
            'shipped_at'         => $this->shipped_at?->toDateTimeString(),
            'delivered_at'       => $this->delivered_at?->toDateTimeString(),

            // Pembayaran (dummy)
            'payment_method'     => $this->payment_method,
            'payment_channel'    => $this->payment_channel,
            'transaction_code'   => $this->transaction_code,
            'va_number'          => $this->va_number,
            'qr_payload'         => $this->qr_payload,
            'payment_expires_at' => $this->payment_expires_at?->toDateTimeString(),
            'paid_at'            => $this->paid_at?->toDateTimeString(),
            'is_paid'            => $this->isPaid(),
            'is_payment_expired' => $this->isPaymentExpired(),

            // Timeline pengiriman (untuk stepper di frontend)
            'shipping_timeline'  => collect(Order::SHIPPING_FLOW)->map(fn ($step) => [
                'key'   => $step,
                'label' => self::SHIPPING_LABELS[$step] ?? $step,
                'done'  => array_search($this->shipping_status, Order::SHIPPING_FLOW, true)
                            >= array_search($step, Order::SHIPPING_FLOW, true),
            ]),

            'created_at'         => $this->created_at?->toDateTimeString(),
            'items' => $this->whenLoaded('items', fn () =>
                $this->items->map(fn ($i) => [
                    'product_name' => $i->product_name,
                    'size'         => $i->size,
                    'price'        => (float) $i->price,
                    'quantity'     => $i->quantity,
                    'subtotal'     => (float) $i->subtotal,
                ])),
        ];
    }
}
