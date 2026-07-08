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

// ---------- Perlu login (Sanctum) ----------
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/checkout', [CheckoutController::class, 'store']); // WAJIB login

    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
});
