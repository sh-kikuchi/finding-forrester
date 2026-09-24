<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/checkout');

        $response->assertRedirect(route('login'));
    }

    public function test_an_individual_user_with_items_in_the_cart_can_view_checkout(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['is_for_sale' => true]);
        Stock::factory()->create(['book_id' => $book->id, 'stock' => 5]);
        $this->actingAs($user)->post('/cart/items', ['book_id' => $book->id, 'quantity' => 1]);

        $response = $this->actingAs($user)->get('/checkout');

        $response->assertOk();
    }

    public function test_an_admin_cannot_access_checkout(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/checkout')->assertForbidden();
        $this->actingAs($admin)->post('/checkout')->assertForbidden();
    }
}
