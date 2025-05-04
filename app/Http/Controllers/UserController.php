<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function account(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = $request->input('per_page', 10);

        $currentOrders = $user->orders()
            ->whereNotIn('status', ['новый', 'в обработке', 'доставляется'])
            ->with(['products' => function($query) {
                $query->select(
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
            ->paginate($perPage, ['id', 'status', 'created_at']);

        $orderHistory = $user->orders()
            ->whereIn('status', ['завершен', 'отменен'])
            ->with(['products' => function($query) {
                $query->select(
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
            ->paginate($perPage, ['id', 'status', 'created_at']);

        return response()->json([
            'user' => $user->only('id', 'name', 'email'),
            'current_orders' => $currentOrders,
            'order_history' => $orderHistory
        ]);
    }
}
