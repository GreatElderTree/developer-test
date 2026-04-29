<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Models\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_matching_products(): void
    {
        ProductModel::create(['name' => 'Widget', 'price' => 5000]);
        ProductModel::create(['name' => 'Gadget', 'price' => 3000]);

        $this->getJson(route('products.search', ['q' => 'wid']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Widget');
    }

    public function test_empty_query_returns_all_products(): void
    {
        ProductModel::create(['name' => 'Widget', 'price' => 5000]);
        ProductModel::create(['name' => 'Gadget', 'price' => 3000]);

        $this->getJson(route('products.search'))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_results_are_paginated(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            ProductModel::create(['name' => "Product {$i}", 'price' => 1000]);
        }

        $response = $this->getJson(route('products.search', ['page' => 2]))
            ->assertOk();

        $this->assertEquals(2,  $response->json('meta.current_page'));
        $this->assertEquals(2,  $response->json('meta.last_page'));
        $this->assertEquals(12, $response->json('meta.total'));
        $this->assertCount(2,   $response->json('data'));
    }

    public function test_response_has_expected_shape(): void
    {
        ProductModel::create(['name' => 'Widget', 'price' => 5000]);

        $this->getJson(route('products.search'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'price']],
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    }

    public function test_search_is_case_insensitive(): void
    {
        ProductModel::create(['name' => 'Widget', 'price' => 5000]);

        $this->getJson(route('products.search', ['q' => 'WIDGET']))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
