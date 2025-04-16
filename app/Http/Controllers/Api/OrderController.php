<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request)
    {
        // Проверка аутентификации
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Требуется авторизация'], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $request->validated();

        // Получаем корзину с подгруженными продуктами
        $cartItems = $user->cartItems()->with('product')->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['error' => 'Корзина пуста'], Response::HTTP_BAD_REQUEST);
        }

        // Создаем заказ в транзакции
        try {
            DB::beginTransaction();

            $order = $user->orders()->create($validated);

            foreach ($cartItems as $item) {
                $order->products()->attach($item->product_id, [
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                ]);
            }

            $user->cartItems()->delete();

            DB::commit();

            return response()->json(['message' => 'Заказ успешно создан', 'order_id' => $order->id, 'total' => $order->total,], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Ошибка при создании заказа: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function index()
    {
        $orders = auth()->user()->orders()->with(['products' => fn($q) => $q->select('id', 'name', 'price')])
            ->latest()
            ->get(['id', 'total', 'status', 'created_at']);

        return response()->json($orders);
    }

    public function show(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return response()->json(
            $order->load(['products' => fn($q) => $q->select('id', 'name', 'price')])
        );
    }
}
