<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

// ---------- Publik ----------
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product:slug}', [ProductController::class, 'show']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Keranjang: tamu (header X-Cart-Token) maupun user login
Route::get('/cart', [CartController::class, 'show']);
Route::post('/cart/items', [CartController::class, 'add']);
Route::patch('/cart/items/{item}', [CartController::class, 'update']);
Route::delete('/cart/items/{item}', [CartController::class, 'remove']);

// Chatbot: bisa diakses tamu; status auth dideteksi otomatis dari token bila ada
Route::post('/chat/start', [ChatController::class, 'start']);
Route::post('/chat/message', [ChatController::class, 'message']);
Route::post('/chat/admin', [ChatController::class, 'switchToAdmin']);
Route::get('/chat/{session}/messages', [ChatController::class, 'messages']);

// ---------- Perlu login (Sanctum) ----------
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/checkout/options', [CheckoutController::class, 'options']);
    Route::post('/checkout', [CheckoutController::class, 'store']); // WAJIB login

    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders/{id}/pay', [OrderController::class, 'pay']);                 // bayar (dummy)
    Route::post('/orders/{id}/shipping/advance', [OrderController::class, 'advanceShipping']); // simulasi kirim
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
});
