<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Cart;
use Illuminate\Support\Facades\Session;
use Illuminate\Database\Eloquent\Collection;

class CartService
{
    protected $typeLimits = [
        'pizza' => 10,
        'drink' => 20
    ];

    public function addProductToCart($user, Product $product, int $quantity = 1): array
    {
        $this->validateProductType($product);

        if ($user) {
            return $this->addForAuthenticatedUser($user, $product, $quantity);
        }

        return $this->addForGuest($product, $quantity);
    }

    protected function validateProductType(Product $product): void
    {
        if (!array_key_exists($product->type, $this->typeLimits)) {
            throw new \InvalidArgumentException('Неизвестный тип продукта');
        }
    }

    protected function addForAuthenticatedUser($user, Product $product, int $quantity): array
    {
        $limit = $this->typeLimits[$product->type];
        $currentQuantity = $this->getUserProductTypeQuantity($user->id, $product->type);

        $this->checkQuantityLimit($currentQuantity + $quantity, $limit, $product->type);

        $cartItem = Cart::firstOrNew([
            'user_id' => $user->id,
            'product_id' => $product->id
        ]);

        $cartItem->quantity += $quantity;
        $cartItem->save();

        return [
            'cart' => $this->getUserCart($user->id),
            'added_product' => $product->only('id', 'name', 'price')
        ];
    }

    protected function addForGuest(Product $product, int $quantity): array
    {
        $limit = $this->typeLimits[$product->type];
        $currentQuantity = $this->getGuestProductTypeQuantity($product->type);

        $this->checkQuantityLimit($currentQuantity + $quantity, $limit, $product->type);

        $cart = Session::get('cart', []);
        $cart[$product->id] = ($cart[$product->id] ?? 0) + $quantity;
        Session::put('cart', $cart);

        return [];
    }

    protected function getUserProductTypeQuantity(int $userId, string $productType): int
    {
        return Cart::where('user_id', $userId)
            ->whereHas('product', function($query) use ($productType) {
                $query->where('type', $productType);
            })
            ->sum('quantity');
    }

    protected function getGuestProductTypeQuantity(string $productType): int
    {
        $cart = Session::get('cart', []);
        $quantity = 0;

        foreach ($cart as $id => $qty) {
            $product = Product::find($id);
            if ($product && $product->type === $productType) {
                $quantity += $qty;
            }
        }

        return $quantity;
    }

    protected function checkQuantityLimit(int $quantity, int $limit, string $productType): void
    {
        if ($quantity > $limit) {
            throw new \LogicException("Максимальное количество $productType в корзине - $limit");
        }
    }

    public function getUserCart(int $userId): array
    {
        $cartItems = Cart::with('product:id,name,price')
            ->where('user_id', $userId)
            ->get();

        return [
            'items' => $cartItems->map(function($item) {
                return [
                    'product' => $item->product,
                    'quantity' => $item->quantity
                ];
            })->toArray()
        ];
    }

    public function getGuestCart(): array
    {
        $cart = Session::get('cart', []);
        $products = Product::whereIn('id', array_keys($cart))
            ->get(['id', 'name', 'price'])
            ->keyBy('id');

        return [
            'items' => array_map(function($quantity, $productId) use ($products) {
                return [
                    'product' => $products[$productId],
                    'quantity' => $quantity
                ];
            }, $cart, array_keys($cart))
        ];
    }
}
