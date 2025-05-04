<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'email' => $this->faker->safeEmail,
            'address' => '123 Test Street',
            'phone' => $this->faker->phoneNumber,
            'delivery_time' => now()->addDays(3),
            'status' => 'В работе',


        ];
    }
}
