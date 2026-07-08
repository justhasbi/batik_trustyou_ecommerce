<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductSize;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // Tamu memakai header X-Cart-Token; user login memakai user_id.
    protected function resolveCart(Request $request): Cart
    {
        if ($request->user()) {
            return Cart::firstOrCreate(['user_id' => $request->user()->id]);
        }

        $token = $request->header('X-Cart-Token');
        abort_if(! $token, 400, 'X-Cart-Token header wajib untuk tamu.');

        return Cart::firstOrCreate(['session_id' => $token]);
    }

    protected function loadCart(Cart $cart): Cart
    {
        return $cart->load(['items.product.primaryImage', 'items.size']);
    }

    public function show(Request $request)
    {
        $cart = $this->loadCart($this->resolveCart($request));
        return new CartResource($cart);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id'      => ['required', 'exists:products,id'],
            'product_size_id' => ['nullable', 'exists:product_sizes,id'],
            'quantity'        => ['required', 'integer', 'min:1'],
        ]);

        // Cek stok (per ukuran bila ada, jika tidak pakai stok produk)
        if (! empty($data['product_size_id'])) {
            $size = ProductSize::find($data['product_size_id']);
            abort_if($size->stock < $data['quantity'], 422, 'Stok ukuran tidak mencukupi.');
        } else {
            $product = Product::find($data['product_id']);
            abort_if($product->stock < $data['quantity'], 422, 'Stok tidak mencukupi.');
        }

        $cart = $this->resolveCart($request);

        $item = $cart->items()
            ->where('product_id', $data['product_id'])
            ->where('product_size_id', $data['product_size_id'] ?? null)
            ->first();

        if ($item) {
            $item->increment('quantity', $data['quantity']);
        } else {
            $cart->items()->create($data);
        }

        return new CartResource($this->loadCart($cart));
    }

    public function update(Request $request, CartItem $item)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $item->update(['quantity' => $data['quantity']]);

        return new CartResource($this->loadCart($item->cart));
    }

    public function remove(Request $request, CartItem $item)
    {
        $cart = $item->cart;
        $item->delete();

        return new CartResource($this->loadCart($cart));
    }
}
