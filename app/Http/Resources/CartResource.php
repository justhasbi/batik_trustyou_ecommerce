<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray($request): array
    {
        $items = $this->items->map(function ($item) {
            $lineTotal = (float) $item->product->price * $item->quantity;
            return [
                'id'           => $item->id,
                'product_id'   => $item->product_id,
                'product_name' => $item->product->name,
                'slug'         => $item->product->slug,
                'image'        => $item->product->primaryImage?->path,
                'size'         => $item->size?->size,
                'size_id'      => $item->product_size_id,
                'price'        => (float) $item->product->price,
                'quantity'     => $item->quantity,
                'line_total'   => $lineTotal,
            ];
        });

        return [
            'id'    => $this->id,
            'items' => $items,
            'count' => $items->sum('quantity'),
            'total' => $items->sum('line_total'),
        ];
    }
}
