<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'book_id' => Book::factory(),
            'seller_id' => User::factory()->admin(),
            'title' => fake()->sentence(3),
            'price' => fake()->numberBetween(500, 3000),
            'quantity' => fake()->numberBetween(1, 3),
        ];
    }
}
