<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use App\Services\CartService;

class UserOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_their_orders()
    {
        $user = User::factory()->create();
        Order::factory()->count(3)->create(['user_id' => $user->id]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/users/me/orders');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'status',
                    'user' => ['id', 'name'],
                    'products' => [
                        '*' => ['id', 'name', 'price']
                    ]
                ]
            ])
            ->assertJsonCount(3);
    }

    public function test_user_cannot_view_other_users_orders()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Order::factory()->create(['user_id' => $user2->id]);

        $token = $user1->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/users/me/orders');

        $response->assertStatus(Response::HTTP_OK)
            ->assertExactJson([]);
    }

    public function test_user_can_view_specific_order()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson("/api/users/me/orders/{$order->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['id' => $order->id]);
    }

    public function test_user_cannot_view_nonexistent_order()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/users/me/orders/999');

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_user_can_create_order()
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $futureTime = Carbon::now()->addHour()->format('Y-m-d H:i:s');

        // Добавляем продукты в корзину пользователя через сервис
        $cartService = app(CartService::class);
        $cartService->addProductToCart($user, $product1, 2);
        $cartService->addProductToCart($user, $product2, 1);


        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])
            ->postJson('/api/users/me/orders', [
                'address' => 'Test Address',
                'phone' => '1234567890',
                'email' => $user->email,
                'delivery_time' => $futureTime
            ]);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure(['message', 'order_id', 'total']);


        $user->refresh(); // Обновляем модель пользователя
        $updatedCartItems = $user->cartItems; // Получаем элементы корзины через отношение
        $this->assertCount(0, $updatedCartItems, 'Корзина пользователя не была очищена после создания заказа.');

    }

    public function test_order_creation_fails_with_invalid_data()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/users/me/orders', [
            'items' => [],
            'address' => ''
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
