<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        // Создание тестовых продуктов
        $products = [
            [
                'name' => 'Пепперони',
                'description' => 'Пицца с пепперони и сыром',
                'price' => 499,
                'type' => 'pizza',
            ],
            [
                'name' => 'Маргарита',
                'description' => 'Классическая пицца с томатами и моцареллой',
                'price' => 599,
                'type' => 'pizza',
            ],
            [
                'name' => 'Гавайская',
                'description' => 'Пицца с ветчиной и ананасами',
                'price' => 799,
                'type' => 'pizza',
            ],
            [
                'name' => 'Спрайт',
                'description' => 'Газированный напиток',
                'price' => 79,
                'type' => 'drink',
            ],
            [
                'name' => 'Сок',
                'description' => 'Ананасовый сок',
                'price' => 69,
                'type' => 'drink',
            ],
        ];

        // Добавление продуктов в базу данных
        foreach ($products as $product) {
            Product::create($product);
        }

    }

}
