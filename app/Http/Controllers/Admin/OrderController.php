<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderStatusUpdateRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    // Список всех заказов
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);

        $orders = Order::with(['user:id,name', 'products:id,name,price'])
            ->latest()
            ->paginate($perPage);

        return response()->json($orders);
    }

    // Просмотр заказа
    public function show(Order $order): JsonResponse
    {
        return response()->json(
            $order->load([
                'user:id,name,email',
                'products:id,name,price'
            ])
        );
    }

    // Обновление статуса
    public function updateStatus(OrderStatusUpdateRequest $request, Order $order): JsonResponse
    {
        $order->update($request->validated());

        return response()->json([
            'message' => 'Статус заказа обновлен',
            'order_id' => $order->id,
            'new_status' => $order->status
        ]);
    }
}
