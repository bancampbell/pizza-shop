<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{


    public function account(): JsonResponse
    {
        $user = Auth::user();

        $orders = $user->orders()
            ->with(['products' => function($q) {
                $q->select(
                    'products.id',
                    'products.name',
                    'products.price',
                    'order_product.order_id as pivot_order_id',
                    'order_product.product_id as pivot_product_id',
                    'order_product.quantity as pivot_quantity',
                    'order_product.price as pivot_price'
                );
            }])
            ->orderBy('created_at', 'desc')
            ->get(['id', 'status', 'created_at']);

        return response()->json([
            'user' => $user->only('id', 'name', 'email'),
            'current_orders' => $orders->whereNotIn('status', ['новый', 'в обработке', 'доставляется']),
            'order_history' => $orders->whereIn('status', ['завершен', 'отменен'])
        ]);
    }
}
