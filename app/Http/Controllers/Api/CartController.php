<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{

    public function index()
    {
        $cart = Session::get('cart', []);
        return response()->json($cart);
    }


    // Добавление продукта в корзину
    public function add(Request $request, $productId)
    {
        $product = Product::find($productId);
        if (!$product) {
            return response()->json(['message' => 'Товар не найден'], 404);
        }


        $cart = Session::get('cart', []);

        // Проверяем, есть ли продукт уже в корзине
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $request->quantity ?? 1;
        } else {
            $cart[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'type' => $product->type,
                'quantity' => $request->quantity ?? 1,
            ];
        }

        Session::put('cart', $cart);
        return response()->json(['message' => 'Товар добавлен в корзину', 'cart' => $cart]);
    }


    // Удаление продукта из корзины
    public function remove($productId)
    {
        $cart = Session::get('cart', []);

        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            Session::put('cart', $cart);
            return response()->json(['message' => 'Товар удален из корзины', 'cart' => $cart]);
        }

        return response()->json(['message' => 'Товар не найден в корзине'], 404);
    }


    // Очистка корзины
    public function clear()
    {
        Session::forget('cart');
        return response()->json(['message' => 'Корзина очищена']);
    }

}
