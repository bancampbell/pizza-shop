<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_product_to_cart()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['type' => 'pizza']);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/cart/add/{$product->id}", [
            'quantity' => 1,
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => 'Товар добавлен в корзину']);
    }

    public function test_adding_product_exceeds_limit_returns_error()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['type' => 'pizza']);
        $token = $user->createToken('test-token')->plainTextToken;

        // Добавляем максимальное количество
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/cart/add/{$product->id}", [
            'quantity' => 10,
        ]);

        // Пытаемся добавить еще
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/cart/add/{$product->id}", [
            'quantity' => 1,
        ]);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson(['error' => 'Максимальное количество pizza в корзине - 10']);
    }

    public function test_guest_can_add_product_to_cart()
    {
        $product = Product::factory()->create(['type' => 'pizza']);

        $response = $this->postJson("/api/cart/add/{$product->id}", [
            'quantity' => 1,
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => 'Товар добавлен в корзину']);
    }

    public function test_user_can_view_cart()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['type' => 'pizza']);

        // Явное добавление в корзину через модель
        $cart = $user->cart()->create();
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($user);
        $response = $this->getJson('/api/cart');

        // Проверяем статус и содержимое
        $response->assertStatus(Response::HTTP_OK);
        $responseData = $response->json();

        // Проверяем структуру ответа
        $this->assertArrayHasKey('items', $responseData);
        $this->assertArrayHasKey('total', $responseData);

        // Ищем наш продукт в items
        $found = false;
        foreach ($responseData['items'] as $item) {
            if ($item['product']['id'] == $product->id) {
                $this->assertEquals(2, $item['quantity']);
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Product not found in cart items');
    }

    public function test_user_can_remove_product_from_cart()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;


        // Сначала добавляем продукт
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/cart/add/{$product->id}");

        // Затем удаляем
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/cart/remove/{$product->id}");

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_user_can_clear_cart()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/cart/clear');

        $response->assertStatus(Response::HTTP_OK);
    }
}
