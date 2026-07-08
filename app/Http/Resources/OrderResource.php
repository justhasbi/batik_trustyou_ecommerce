<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'order_number'    => $this->order_number,
            'status'          => $this->status,
            'shipping_status' => $this->shipping_status,
            'subtotal'        => (float) $this->subtotal,
            'shipping_cost'   => (float) $this->shipping_cost,
            'total'           => (float) $this->total,
            'recipient_name'  => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'shipping_address'=> $this->shipping_address,
            'courier'         => $this->courier,
            'tracking_number' => $this->tracking_number,
            'created_at'      => $this->created_at?->toDateTimeString(),
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
