<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\ProductSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    // Endpoint ini dilindungi middleware auth:sanctum (lihat routes/api.php)
    public function store(Request $request)
    {
        $data = $request->validate([
            'recipient_name'   => ['required', 'string', 'max:255'],
            'recipient_phone'  => ['required', 'string', 'max:30'],
            'shipping_address' => ['required', 'string'],
            'courier'          => ['nullable', 'string', 'max:50'],
            'shipping_cost'    => ['nullable', 'numeric', 'min:0'],
        ]);

        $cart = Cart::where('user_id', $request->user()->id)
            ->with(['items.product', 'items.size'])
            ->first();

        abort_if(! $cart || $cart->items->isEmpty(), 422, 'Keranjang kosong.');

        $order = DB::transaction(function () use ($cart, $data, $request) {
            $subtotal = 0;

            $order = Order::create([
                'user_id'          => $request->user()->id,
                'order_number'     => 'INV-' . strtoupper(Str::random(8)),
                'subtotal'         => 0,
                'shipping_cost'    => $data['shipping_cost'] ?? 0,
                'total'            => 0,
                'status'           => 'pending',
                'shipping_status'  => 'not_shipped',
                'recipient_name'   => $data['recipient_name'],
                'recipient_phone'  => $data['recipient_phone'],
                'shipping_address' => $data['shipping_address'],
                'courier'          => $data['courier'] ?? null,
            ]);

            foreach ($cart->items as $item) {
                $price    = (float) $item->product->price;
                $lineTotal = $price * $item->quantity;
                $subtotal += $lineTotal;

                $order->items()->create([
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product->name,   // snapshot
                    'size'         => $item->size?->size,
                    'price'        => $price,                  // snapshot
                    'quantity'     => $item->quantity,
                    'subtotal'     => $lineTotal,
                ]);

                // Kurangi stok
                if ($item->product_size_id) {
                    ProductSize::where('id', $item->product_size_id)
                        ->decrement('stock', $item->quantity);
                } else {
                    $item->product->decrement('stock', $item->quantity);
                }
            }

            $order->update([
                'subtotal' => $subtotal,
                'total'    => $subtotal + ($data['shipping_cost'] ?? 0),
            ]);

            // Kosongkan keranjang
            $cart->items()->delete();

            return $order;
        });

        return new OrderResource($order->load('items'));
    }
}
