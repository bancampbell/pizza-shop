<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_view_products_list()
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(3, 'data');
    }

    public function test_anyone_can_view_single_product()
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['id' => $product->id]);
    }

    public function test_viewing_nonexistent_product_returns_404()
    {
        $response = $this->getJson('/api/products/999');

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }
}
