<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Session;

class CartService
{
    protected $typeLimits = [
        'pizza' => 10,
        'drink' => 20,
    ];

    public function getCart(?User $user = null): Cart
    {
        if ($user) {
            return $user->cart()->firstOrCreate();
        }

        $cartId = Session::get('cart_id');
        if (!$cartId) {
            $cart = Cart::create();
            Session::put('cart_id', $cart->id);
            return $cart;
        }

        return Cart::findOrFail($cartId);
    }

    public function addProductToCart(?User $user, Product|int $product, int $quantity = 1): array
    {
        $product = $product instanceof Product
            ? $product
            : Product::findOrFail($product);

        $this->validateProductType($product);

        $cart = $this->getCart($user);
        $this->checkQuantityLimits($cart, $product, $quantity);

        $cartItem = $cart->items()->firstOrNew([
            'product_id' => $product->id
        ]);

        $cartItem->quantity += $quantity;
        $cartItem->save();

        return [
            'cart' => $this->formatCart($cart),
            'added_product' => $product->only('id', 'name', 'price'),
        ];
    }

    public function removeProduct(?User $user, int $productId): void
    {
        $cart = $this->getCart($user);
        $deleted = $cart->items()->where('product_id', $productId)->delete();

        if ($deleted === 0) {
            throw new ModelNotFoundException('Товар не найден в корзине');
        }
    }

    public function updateProductQuantity(?User $user, int $productId, int $quantity): array
    {
        if ($quantity <= 0) {
            $this->removeProduct($user, $productId);
            return $this->getFormattedCart($user);
        }

        $product = Product::findOrFail($productId);
        $cart = $this->getCart($user);

        $this->checkQuantityLimits($cart, $product, $quantity, true);

        $cart->items()->updateOrCreate(
            ['product_id' => $productId],
            ['quantity' => $quantity]
        );

        return $this->getFormattedCart($user);
    }

    public function clearCart(?User $user): void
    {
        $cart = $this->getCart($user);
        $cart->items()->delete();
    }

    public function getFormattedCart(?User $user): array
    {
        $cart = $this->getCart($user);
        return $this->formatCart($cart);
    }

    protected function formatCart(Cart $cart): array
    {
        $cart->load('items.product:id,name,price');

        return [
            'items' => $cart->items->map(function (CartItem $item) {
                return [
                    'product' => $item->product,
                    'quantity' => $item->quantity,
                ];
            })->toArray(),
            'total' => $this->calculateTotal($cart),
        ];
    }

    protected function calculateTotal(Cart $cart): float
    {
        return $cart->items->sum(function (CartItem $item) {
            return $item->product->price * $item->quantity;
        });
    }

    protected function validateProductType(Product $product): void
    {
        if (!array_key_exists($product->type, $this->typeLimits)) {
            throw new \InvalidArgumentException('Неизвестный тип продукта');
        }
    }

    protected function checkQuantityLimits(
        Cart $cart,
        Product $product,
        int $quantity,
        bool $isUpdate = false
    ): void {
        $currentQuantity = $cart->items()
            ->whereHas('product', fn($q) => $q->where('type', $product->type))
            ->sum('quantity');

        if ($isUpdate) {
            $existingItem = $cart->items()
                ->where('product_id', $product->id)
                ->first();

            if ($existingItem) {
                $currentQuantity -= $existingItem->quantity;
            }
        }

        $limit = $this->typeLimits[$product->type] ?? 0;

        if (($currentQuantity + $quantity) > $limit) {
            throw new \LogicException("Максимальное количество {$product->type} в корзине - {$limit}");
        }
    }
}
