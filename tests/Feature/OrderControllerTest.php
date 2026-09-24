<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Order;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_individual_user_can_view_their_own_order_history(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['is_for_sale' => true, 'price' => 1000]);
        Stock::factory()->create(['book_id' => $book->id, 'stock' => 5]);
        $this->actingAs($user)->post('/cart/items', ['book_id' => $book->id, 'quantity' => 1]);
        $this->actingAs($user)->post('/checkout');

        $response = $this->actingAs($user)->get('/orders');

        $response->assertOk();
    }

    public function test_an_individual_user_cannot_view_another_users_order(): void
    {
        $owner = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id]);
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->get("/orders/{$order->id}");

        $response->assertForbidden();
    }

    public function test_an_admin_cannot_access_order_history(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/orders')->assertForbidden();
    }
}
