<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()->orders()->latest()->get();
        return OrderResource::collection($orders);
    }

    public function show(Request $request, $id)
    {
        $order = $request->user()->orders()->with('items')->findOrFail($id);
        return new OrderResource($order);
    }
}
