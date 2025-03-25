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

    public function index(Request $request): JsonResponse
    {
        if ($request->user()) {
            // Если пользователь аутентифицирован, получаем корзину из базы данных
            $userId = $request->user()->id;
            $cartItems = Cart::where('user_id', $userId)->get();

            // Форматируем данные в том же формате, что и сессия
            $cart = [];
            foreach ($cartItems as $item) {
                $cart[$item->product_id] = $item->quantity;
            }
        } else {
            // Если пользователь не аутентифицирован, получаем корзину из сессии
            $cart = Session::get('cart', []);
        }

        return response()->json($cart);
    }

    // Добавление продукта в корзину
    public function add(Request $request, $productId): JsonResponse
    {
        // Получаем количество товара из запроса (по умолчанию 1)
        $quantity = $request->input('quantity', 1);

        // Проверяем аутентифицирован ли пользователь
        if ($request->user()) {
            // Если пользователь аутентифицирован, добавляем товар в базу данных
            $userId = $request->user()->id;

            // Проверяем, есть ли уже такой товар в корзине
            $cartItem = Cart::where('user_id', $userId)->where('product_id', $productId)->first();

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

            return response()->json(['message' => 'Товар добавлен в корзину в базу данных']);
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

            return response()->json(['message' => 'Товар добавлен в корзину в сессии']);
        }
    }

    // Удаление продукта из корзины
    public function remove(Request $request, $productId): JsonResponse
    {
        if ($request->user()) {
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
    public function clear(Request $request): JsonResponse
    {
        if ($request->user()) {
            Cart::where('user_id', $request->user()->id)->delete();
        } else {
            Session::forget('cart');
        }

        return response()->json(['message' => 'Корзина очищена']);
    }

}
