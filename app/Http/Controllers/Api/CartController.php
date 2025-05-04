<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;


class CartController extends Controller
{

    public function index(Request $request)
    {
        // Явная проверка аутентификации
        if (!auth()->check()) {
            return response()->json([], Response::HTTP_UNAUTHORIZED);
        }

        // Получаем корзину с продуктами
        $cartItems = Cart::with('product:id,name,price')
            ->where('user_id', auth()->id())
            ->get();

        // Формируем ответ
        $response = [];
        foreach ($cartItems as $item) {
            $response[$item->product_id] = $item->quantity;
        }

        return response()->json($response);
    }

    // Добавление продукта в корзину
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function add(Request $request, $productId): JsonResponse
    {
        try {
            $quantity = $request->input('quantity', 1);
            $product = Product::findOrFail($productId);

            $result = $this->cartService->addProductToCart(
                $request->user(),
                $product,
                $quantity
            );

            return response()->json([
                'message' => 'Товар добавлен в корзину',
                'cart' => $result['cart'] ?? null,
                'added_product' => $result['added_product'] ?? null
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\LogicException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function show(Request $request): JsonResponse
    {
        $cartData = $request->user()
            ? $this->cartService->getUserCart($request->user()->id)
            : $this->cartService->getGuestCart();

        return response()->json(['cart' => $cartData]);
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
