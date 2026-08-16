<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\ProductSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()->orders()->with('items')->latest()->get();
        return OrderResource::collection($orders);
    }

    public function show(Request $request, $id)
    {
        $order = $request->user()->orders()->with('items')->findOrFail($id);
        return new OrderResource($order);
    }

    /**
     * Pembayaran DUMMY: menandai order sebagai dibayar.
     */
    public function pay(Request $request, $id)
    {
        $order = $request->user()->orders()->with('items')->findOrFail($id);

        abort_if($order->payment_method === 'cod', 422, 'Pesanan COD dibayar saat barang diterima.');
        abort_if($order->isPaid(), 422, 'Pesanan ini sudah dibayar.');
        abort_if($order->status === 'cancelled', 422, 'Pesanan sudah dibatalkan.');
        abort_if($order->isPaymentExpired(), 422, 'Batas waktu pembayaran sudah lewat.');

        $order->update([
            'status'          => 'paid',
            'paid_at'         => now(),
            'shipping_status' => 'packed', // otomatis masuk tahap dikemas
        ]);

        return new OrderResource($order->fresh('items'));
    }

    /**
     * Simulasi (DUMMY) kemajuan pengiriman: maju satu tahap tiap dipanggil.
     * not_shipped -> packed -> shipped -> in_transit -> delivered.
     * Saat mencapai "delivered", status pesanan menjadi "completed".
     */
    public function advanceShipping(Request $request, $id)
    {
        $order = $request->user()->orders()->with('items')->findOrFail($id);

        abort_unless($order->isPaid(), 422, 'Selesaikan pembayaran dulu sebelum melacak pengiriman.');
        abort_if($order->shipping_status === 'delivered', 422, 'Pesanan sudah sampai tujuan.');

        $flow    = Order::SHIPPING_FLOW;
        $current = array_search($order->shipping_status, $flow, true);
        $next    = $flow[$current + 1] ?? 'delivered';

        $updates = ['shipping_status' => $next];

        if ($next === 'shipped') {
            // Terbitkan nomor resi saat barang dikirim.
            $updates['tracking_number'] = strtoupper(($order->courier ? substr(preg_replace('/[^A-Z]/', '', strtoupper($order->courier)), 0, 3) : 'RSI'))
                . now()->format('ymd') . strtoupper(Str::random(6));
            $updates['shipped_at'] = now();
        }

        if ($next === 'delivered') {
            $updates['delivered_at'] = now();
            $updates['status']       = 'completed';
            // COD dianggap lunas saat diterima.
            if (! $order->paid_at) {
                $updates['paid_at'] = now();
            }
        }

        $order->update($updates);

        return new OrderResource($order->fresh('items'));
    }

    /**
     * Membatalkan pesanan yang belum dibayar & mengembalikan stok.
     */
    public function cancel(Request $request, $id)
    {
        $order = $request->user()->orders()->with('items')->findOrFail($id);

        abort_if($order->isPaid(), 422, 'Pesanan yang sudah dibayar tidak bisa dibatalkan.');
        abort_if($order->status === 'cancelled', 422, 'Pesanan sudah dibatalkan.');
        abort_if($order->shipping_status !== 'not_shipped', 422, 'Pesanan sudah diproses pengiriman.');

        DB::transaction(function () use ($order) {
            // Kembalikan stok
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    // Kembalikan ke ukuran bila ada, jika tidak ke stok produk.
                    $size = $item->size
                        ? ProductSize::where('product_id', $item->product_id)->where('size', $item->size)->first()
                        : null;
                    if ($size) {
                        $size->increment('stock', $item->quantity);
                    } else {
                        $item->product?->increment('stock', $item->quantity);
                    }
                }
            }

            $order->update(['status' => 'cancelled']);
        });

        return new OrderResource($order->fresh('items'));
    }
}
