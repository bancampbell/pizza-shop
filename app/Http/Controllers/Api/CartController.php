<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{

    public function index(): JsonResponse
    {
        $cart = Session::get('cart', []);
        return response()->json($cart);
    }

    // Добавление продукта в корзину
    public function add(Request $request, $productId): JsonResponse
    {
        $quantity = $request->input('quantity', 1);

        // Если пользователь аутентифицирован, сохраняем корзину в базе данных
        if (Auth::check()) {
            $userId = Auth::id();

            // Проверяем, есть ли уже такой товар в корзине
            $cartItem = Cart::where('user_id', $userId)
                ->where('product_id', $productId)
                ->first();

            if ($cartItem) {
                // Если товар уже есть в корзине, обновляем количество
                $cartItem->quantity += $quantity;
                $cartItem->save();
            } else {
                // Если товара нет в корзине, создаем новую запись
                Cart::create([
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                ]);
            }
        } else {
            // Если пользователь не аутентифицирован, сохраняем корзину в сессии
            $cart = Session::get('cart', []);

            // Проверяем, есть ли уже такой товар в корзине
            if (isset($cart[$productId])) {
                // Если товар уже есть в корзине, увеличиваем количество
                $cart[$productId] += $quantity;
            } else {
                // Если товара нет в корзине, добавляем его
                $cart[$productId] = $quantity;
            }
            // Сохраняем обновленную корзину в сессии
            Session::put('cart', $cart);
        }

        return response()->json(['message' => 'Продукт добавлен в корзину']);
    }

    // Удаление продукта из корзины
    public function remove(string $productId): JsonResponse
    {
        if (Auth::check()) {
            // Если пользователь аутентифицирован, удаляем товар из базы данных
            $deleted = Cart::where('user_id', Auth::id())->where('product_id', $productId)->delete();

            if ($deleted === 0) {
                // Если товар не найден в корзине
                return response()->json(['message' => 'Товар не найден в корзине'], Response::HTTP_NOT_FOUND);
            }
        } else {
            // Если пользователь не аутентифицирован, удаляем товар из сессии
            $cart = Session::get('cart', []);

            if (isset($cart[$productId])) {
                unset($cart[$productId]); // Удаляем товар из корзины
                Session::put('cart', $cart); // Обновляем корзину в сессии
            } else {
                // Если товар не найден в корзине
                return response()->json(['message' => 'Товар не найден в корзине'], Response::HTTP_NOT_FOUND);
            }
        }

        return response()->json(['message' => 'Товар удален из корзины']);
    }


    // Очистка корзины
    public function clear(): JsonResponse
    {
        Session::forget('cart');
        return response()->json(['message' => 'Корзина очищена']);
    }

}
