<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\ProductSize;
use App\Support\CheckoutOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    // Daftar opsi pembayaran & pengiriman untuk ditampilkan di halaman checkout.
    public function options()
    {
        return response()->json([
            'payment_methods'  => CheckoutOptions::PAYMENT_METHODS,
            'shipping_options' => CheckoutOptions::SHIPPING_OPTIONS,
        ]);
    }

    // Endpoint ini dilindungi middleware auth:sanctum (lihat routes/api.php)
    public function store(Request $request)
    {
        $data = $request->validate([
            'recipient_name'    => ['required', 'string', 'max:255'],
            'recipient_phone'   => ['required', 'string', 'max:30'],
            'shipping_address'  => ['required', 'string'],
            'shipping_option_id'=> ['required', 'string', 'in:' . implode(',', CheckoutOptions::shippingOptionIds())],
            'payment_method'    => ['required', 'string', 'in:' . implode(',', CheckoutOptions::paymentMethodIds())],
            'payment_channel'   => ['nullable', 'string', 'max:50'],
        ]);

        $shipping = CheckoutOptions::shippingOption($data['shipping_option_id']);
        $method   = CheckoutOptions::paymentMethod($data['payment_method']);

        // Validasi channel bila metode punya daftar channel (mis. e-wallet / bank)
        if (! empty($method['channels'])) {
            abort_unless(
                in_array($data['payment_channel'] ?? null, $method['channels'], true),
                422,
                'Silakan pilih channel pembayaran yang valid.'
            );
        }

        $cart = Cart::where('user_id', $request->user()->id)
            ->with(['items.product', 'items.size'])
            ->first();

        abort_if(! $cart || $cart->items->isEmpty(), 422, 'Keranjang kosong.');

        $order = DB::transaction(function () use ($cart, $data, $shipping, $method, $request) {
            $subtotal     = 0;
            $shippingCost = (float) $shipping['cost'];

            $order = Order::create([
                'user_id'          => $request->user()->id,
                'order_number'     => 'INV-' . strtoupper(Str::random(8)),
                'subtotal'         => 0,
                'shipping_cost'    => $shippingCost,
                'total'            => 0,
                // COD langsung diproses (bayar saat terima); non-COD menunggu pembayaran.
                'status'           => $method['id'] === 'cod' ? 'processing' : 'pending',
                'shipping_status'  => 'not_shipped',
                'recipient_name'   => $data['recipient_name'],
                'recipient_phone'  => $data['recipient_phone'],
                'shipping_address' => $data['shipping_address'],
                'courier'          => $shipping['courier'],
                'shipping_method'  => $shipping['service'],
                'payment_method'   => $method['id'],
                'payment_channel'  => $data['payment_channel'] ?? null,
            ]);

            foreach ($cart->items as $item) {
                $price     = (float) $item->product->price;
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

            $total = $subtotal + $shippingCost;

            // Buat instruksi pembayaran (dummy) berdasarkan metode terpilih.
            $payment = $this->buildPaymentInstruction($order, $method, $total);

            $order->update(array_merge([
                'subtotal' => $subtotal,
                'total'    => $total,
            ], $payment));

            // Kosongkan keranjang
            $cart->items()->delete();

            return $order;
        });

        return new OrderResource($order->load('items'));
    }

    /**
     * Membuat data instruksi pembayaran DUMMY sesuai metode.
     * Mengembalikan array kolom yang akan disimpan ke order.
     */
    private function buildPaymentInstruction(Order $order, array $method, float $total): array
    {
        $trxCode = 'TRX-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));

        $result = [
            'transaction_code'   => $trxCode,
            'va_number'          => null,
            'qr_payload'         => null,
            'payment_expires_at' => now()->addDay(),
            'paid_at'            => null,
        ];

        switch ($method['type']) {
            case 'qr':
                // Payload QR bergaya QRIS (dummy, cukup untuk di-scan sebagai teks).
                $result['qr_payload'] = implode('|', [
                    'BATIKTRUSTYOU',
                    $order->order_number,
                    $trxCode,
                    (int) $total,
                    $method['id'],
                ]);
                break;

            case 'va':
                // Nomor Virtual Account dummy: prefix bank + digit acak.
                $prefix = match ($order->payment_channel) {
                    'BCA'     => '8808',
                    'BNI'     => '8810',
                    'BRI'     => '8820',
                    'Mandiri' => '8830',
                    default   => '8800',
                };
                $result['va_number'] = $prefix . str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
                break;

            case 'cod':
                // Tidak ada instruksi bayar online; tidak ada masa kedaluwarsa.
                $result['payment_expires_at'] = null;
                break;
        }

        return $result;
    }
}
