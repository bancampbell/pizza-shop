<?php

namespace Tests\Feature;


use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard()
    {
        // 1. Создаём админа
        $admin = User::factory()->create(['is_admin' => true]);

        // 2. Создаём токен Sanctum
        $token = $admin->createToken('admin-token')->plainTextToken;

        // 3. Отправляем запрос с токеном
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/admin/dashboard');

        // 4. Проверяем ответ
        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => 'Добро пожаловать в админ-панель']);
    }

    public function test_non_admin_cannot_access_dashboard()
    {
        $user = User::factory()->create();
        $token = $user->createToken('user-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/admin/dashboard');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_admin_can_view_all_orders()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        // Создаем заказы с заполнением всех обязательных полей
        Order::factory()->count(5)->create([
            'user_id' => User::factory(),
            'email' => 'test' . rand(1, 100) . '@example.com',
            'address' => '123 Test Street',
            'phone' => '1234567890'
        ]);

        $token = $admin->createToken('admin-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/admin/orders');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(5, 'data');
    }

    public function test_admin_can_update_order_status()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = Order::factory()->create(['status' => 'В работе']);

        $token = $admin->createToken('admin-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->patchJson("/api/admin/orders/{$order->id}/status", [
            'status' => 'В работе'
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['new_status' => 'В работе']);
    }

    public function test_admin_cannot_update_to_invalid_status()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = Order::factory()->create();

        $token = $admin->createToken('admin-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->patchJson("/api/admin/orders/{$order->id}/status", [
            'status' => 'invalid_status'
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
