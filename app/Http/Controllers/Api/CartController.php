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
        $quantity = $request->input('quantity', 1);
        $product = Product::findOrFail($productId);

        // Лимиты для разных типов продуктов
        $typeLimits = [
            'pizza' => 10,
            'drink' => 20
        ];

        // Проверяем тип продукта
        if (!array_key_exists($product->type, $typeLimits)) {
            return response()->json(['error' => 'Неизвестный тип продукта'], Response::HTTP_BAD_REQUEST);
        }

        $limit = $typeLimits[$product->type];

        if ($request->user()) {
            // Логика для авторизованных пользователей
            $userId = $request->user()->id;

            // Получаем текущее количество этого типа продуктов в корзине
            $currentQuantity = Cart::where('user_id', $userId)
                ->whereHas('product', function($query) use ($product) {
                    $query->where('type', $product->type);
                })
                ->sum('quantity');

            // Проверка лимита
            if (($currentQuantity + $quantity) > $limit) {
                return response()->json([
                    'error' => "Максимальное количество {$product->type} в корзине - $limit"], Response::HTTP_BAD_REQUEST);
            }

            // Добавление в корзину
            $cartItem = Cart::where('user_id', $userId)->where('product_id', $productId)->first();

            if ($cartItem) {
                $cartItem->quantity += $quantity;
                $cartItem->save();
            } else {
                Cart::create([
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                ]);
            }

            return response()->json(['message' => 'Товар добавлен в корзину']);
        } else {
            // Логика для гостевых пользователей
            $cart = Session::get('cart', []);

            // Подсчет текущего количества этого типа продуктов
            $currentQuantity = 0;
            foreach ($cart as $id => $qty) {
                $p = Product::find($id);
                if ($p && $p->type === $product->type) {
                    $currentQuantity += $qty;
                }
            }

            // Проверка лимита
            if (($currentQuantity + $quantity) > $limit) {
                return response()->json([
                    'error' => "Максимальное количество {$product->type} в корзине - $limit"
                ], Response::HTTP_BAD_REQUEST);
            }

            // Добавление в сессию
            if (isset($cart[$productId])) {
                $cart[$productId] += $quantity;
            } else {
                $cart[$productId] = $quantity;
            }

            Session::put('cart', $cart);

            return response()->json(['message' => 'Товар добавлен в корзину']);
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
