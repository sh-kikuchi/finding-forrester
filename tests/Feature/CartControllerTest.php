<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_view_and_add_to_the_cart(): void
    {
        $book = Book::factory()->create(['is_for_sale' => true]);
        Stock::factory()->create(['book_id' => $book->id, 'stock' => 5]);

        $this->get('/cart')->assertOk();

        $response = $this->post('/cart/items', ['book_id' => $book->id, 'quantity' => 1]);

        $response->assertRedirect(route('cart.index'));
        $this->assertSame(1, session('cart')[$book->id]);
    }

    public function test_an_individual_user_can_view_and_add_to_the_cart(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['is_for_sale' => true]);
        Stock::factory()->create(['book_id' => $book->id, 'stock' => 5]);

        $this->actingAs($user)->get('/cart')->assertOk();

        $response = $this->actingAs($user)->post('/cart/items', ['book_id' => $book->id, 'quantity' => 1]);

        $response->assertRedirect(route('cart.index'));
    }

    public function test_an_admin_cannot_view_or_add_to_the_cart(): void
    {
        $admin = User::factory()->admin()->create();
        $book = Book::factory()->create(['user_id' => $admin->id, 'is_for_sale' => true]);
        Stock::factory()->create(['book_id' => $book->id, 'stock' => 5]);

        $this->actingAs($admin)->get('/cart')->assertForbidden();
        $this->actingAs($admin)->post('/cart/items', ['book_id' => $book->id, 'quantity' => 1])->assertForbidden();
    }
}
