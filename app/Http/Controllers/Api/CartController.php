<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Http\Resources\ProductResource;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartService->getFormattedCart($request->user());
        return response()->json($cart);
    }

    public function add(Request $request, int $productId): JsonResponse
    {
        $quantity = $request->input('quantity', 1);

        $result = $this->cartService->addProductToCart(
            $request->user(),
            $productId,
            $quantity
        );

        return response()->json([
            'message' => 'Товар добавлен в корзину',
            'cart' => $result['cart'],
            'added_product' => $result['added_product'],
        ]);
    }

    public function update(Request $request, int $productId): JsonResponse
    {
        $quantity = $request->input('quantity', 1);

        $cart = $this->cartService->updateProductQuantity(
            $request->user(),
            $productId,
            $quantity
        );

        return response()->json([
            'message' => 'Количество товара обновлено',
            'cart' => $cart,
        ]);
    }

    public function remove(Request $request, int $productId): JsonResponse
    {
        $this->cartService->removeProduct(
            $request->user(),
            $productId
        );

        return response()->json([
            'message' => 'Товар удален из корзины'
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $this->cartService->clearCart($request->user());

        return response()->json([
            'message' => 'Корзина очищена'
        ]);
    }

    public function getTotal(Request $request): JsonResponse
    {
        $cart = $this->cartService->getFormattedCart($request->user());

        return response()->json([
            'total' => $cart['total']
        ]);
    }
}
