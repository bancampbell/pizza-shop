<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request)
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['error' => 'Требуется авторизация'], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $request->validated();

        // Получаем корзину пользователя с товарами
        $cart = $user->cart()->with('items.product')->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json(['error' => 'Корзина пуста'], Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();

            $order = $user->orders()->create($validated);

            foreach ($cart->items as $item) {
                $order->products()->attach($item->product_id, [
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                ]);
            }

            // Очищаем корзину
            $cart->items()->delete();

            DB::commit();

            return response()->json([
                'message' => 'Заказ успешно создан',
                'order_id' => $order->id,
                'total' => $order->total,
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Ошибка при создании заказа: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function index()
    {
        $orders = auth()->user()->orders()
            ->with([
                'user:id,name',
                'products' => function ($query) {
                    $query->select(
                        'products.id',
                        'products.name',
                        'products.price',
                        'order_product.quantity',
                        'order_product.price as pivot_price'
                    );
                }])
            ->latest()
            ->get(['orders.id', 'orders.status', 'orders.created_at', 'orders.user_id']);

        return response()->json($orders);
    }

    public function show(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return response()->json(
            $order->load(['products' => function ($query) {
                $query->select(
                    'products.id',
                    'products.name',
                    'products.price',
                    'order_product.quantity',
                    'order_product.price as pivot_price'
                );
            }])
        );
    }
}
