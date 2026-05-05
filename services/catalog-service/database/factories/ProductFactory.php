<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
final class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'id'          => Str::uuid7()->toString(),
            'name'        => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'price'       => $this->faker->randomFloat(2, 1, 1000),
            'category_id' => Category::factory(),
            'sku'         => strtoupper(Str::random(8)),
            'is_active'   => true,
        ];
    }
}