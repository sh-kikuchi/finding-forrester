<?php

namespace Tests\Feature;

use App\Mail\OrderShippedMail;
use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Stock;
use App\Models\User;
use App\Policies\OrderPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SellerOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/shop/orders');

        $response->assertRedirect(route('login'));
    }

    public function test_individual_users_cannot_access_received_orders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/shop/orders')->assertForbidden();
    }

    public function test_seller_sees_order_items_for_their_own_books(): void
    {
        $seller = User::factory()->admin()->create();
        $book = Book::factory()->create(['user_id' => $seller->id]);
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'book_id' => $book->id,
            'seller_id' => $seller->id,
            'title' => $book->title,
        ]);

        $response = $this->actingAs($seller)->get('/shop/orders');

        $response->assertOk();
        $response->assertSee($book->title);
    }

    public function test_seller_does_not_see_order_items_for_another_sellers_books(): void
    {
        $seller = User::factory()->admin()->create();
        $otherSeller = User::factory()->admin()->create();
        $otherBook = Book::factory()->create(['user_id' => $otherSeller->id]);
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'book_id' => $otherBook->id,
            'seller_id' => $otherSeller->id,
            'title' => $otherBook->title,
        ]);

        $response = $this->actingAs($seller)->get('/shop/orders');

        $response->assertOk();
        $response->assertDontSee($otherBook->title);
    }

    public function test_seller_can_ship_their_items_and_the_buyer_is_emailed_via_the_queue(): void
    {
        Mail::fake();
        $this->travelTo('2026-09-24 12:00:00');
        $seller = User::factory()->admin()->create();
        $buyer = User::factory()->create();
        $order = $this->createOrderContainingSellersBook($seller, $buyer);

        $response = $this->actingAs($seller)->post(route('shop.orders.ship', $order));

        $response->assertRedirect();
        $response->assertSessionHas('status', '発送メールを送信キューに登録しました。');
        $this->assertSame('2026-09-24 12:00:00', $order->items->first()->refresh()->shipped_at->format('Y-m-d H:i:s'));
        Mail::assertQueued(
            OrderShippedMail::class,
            fn (OrderShippedMail $mail): bool => $mail->hasTo($buyer->email) && $mail->order->is($order),
        );
    }

    public function test_shipping_groups_the_sellers_items_into_one_email_and_leaves_other_sellers_items_unshipped(): void
    {
        Mail::fake();
        $seller = User::factory()->admin()->create();
        $otherSeller = User::factory()->admin()->create();
        $order = $this->createOrderContainingSellersBook($seller, User::factory()->create());
        $this->addItemToOrder($order, $seller);
        $otherSellersItem = $this->addItemToOrder($order, $otherSeller);

        $this->actingAs($seller)->post(route('shop.orders.ship', $order));

        $sellersItemIds = $order->items()->where('seller_id', $seller->id)->pluck('id')->all();
        $this->assertCount(2, $sellersItemIds);
        $this->assertSame(0, $order->items()->where('seller_id', $seller->id)->whereNull('shipped_at')->count());
        $this->assertNull($otherSellersItem->refresh()->shipped_at);
        Mail::assertQueued(OrderShippedMail::class, 1);
        Mail::assertQueued(
            OrderShippedMail::class,
            fn (OrderShippedMail $mail): bool => $mail->items->pluck('id')->all() === $sellersItemIds,
        );
    }

    public function test_each_seller_in_a_shared_order_ships_and_emails_separately(): void
    {
        Mail::fake();
        $seller = User::factory()->admin()->create();
        $otherSeller = User::factory()->admin()->create();
        $order = $this->createOrderContainingSellersBook($seller, User::factory()->create());
        $otherSellersItem = $this->addItemToOrder($order, $otherSeller);

        $this->actingAs($seller)->post(route('shop.orders.ship', $order));
        $this->actingAs($otherSeller)->post(route('shop.orders.ship', $order));

        $this->assertNotNull($otherSellersItem->refresh()->shipped_at);
        Mail::assertQueued(OrderShippedMail::class, 2);
        Mail::assertQueued(
            OrderShippedMail::class,
            fn (OrderShippedMail $mail): bool => $mail->items->pluck('id')->all() === [$otherSellersItem->id],
        );
    }

    public function test_shipping_an_already_shipped_order_does_not_send_the_email_again(): void
    {
        Mail::fake();
        $seller = User::factory()->admin()->create();
        $order = $this->createOrderContainingSellersBook($seller, User::factory()->create(), ['shipped_at' => now()->subDay()]);

        $response = $this->actingAs($seller)->post(route('shop.orders.ship', $order));

        $response->assertSessionHas('status', 'この注文はすでに発送済みです。');
        Mail::assertNothingQueued();
    }

    public function test_seller_cannot_ship_an_order_without_their_own_books(): void
    {
        Mail::fake();
        $seller = User::factory()->admin()->create();
        $otherSeller = User::factory()->admin()->create();
        $order = $this->createOrderContainingSellersBook($otherSeller, User::factory()->create());

        $response = $this->actingAs($seller)->post(route('shop.orders.ship', $order));

        $response->assertForbidden();
        $this->assertNull($order->items->first()->refresh()->shipped_at);
        Mail::assertNothingQueued();
    }

    public function test_individual_users_cannot_ship_orders(): void
    {
        Mail::fake();
        $buyer = User::factory()->create();
        $order = $this->createOrderContainingSellersBook(User::factory()->admin()->create(), $buyer);

        $this->actingAs($buyer)->post(route('shop.orders.ship', $order))->assertForbidden();

        Mail::assertNothingQueued();
    }

    public function test_order_policy_allows_shipping_only_for_sellers_with_items_in_the_order(): void
    {
        $seller = User::factory()->admin()->create();
        $otherSeller = User::factory()->admin()->create();
        $order = $this->createOrderContainingSellersBook($seller, User::factory()->create());

        $policy = new OrderPolicy;

        $this->assertTrue($policy->ship($seller, $order));
        $this->assertFalse($policy->ship($otherSeller, $order));
    }

    public function test_order_list_groups_the_sellers_items_per_order_with_a_single_ship_button(): void
    {
        $seller = User::factory()->admin()->create();
        $otherSeller = User::factory()->admin()->create();
        $order = $this->createOrderContainingSellersBook($seller, User::factory()->create(), ['title' => '吾輩は猫である', 'price' => 1000, 'quantity' => 1]);
        $this->addItemToOrder($order, $seller, ['title' => '坊っちゃん', 'price' => 500, 'quantity' => 2]);
        $this->addItemToOrder($order, $otherSeller, ['title' => '他店の本']);

        $response = $this->actingAs($seller)->get('/shop/orders');

        $response->assertSee('吾輩は猫である');
        $response->assertSee('坊っちゃん');
        $response->assertDontSee('他店の本');
        $response->assertSee('小計 ¥2,000');
        $this->assertSame(1, substr_count($response->getContent(), route('shop.orders.ship', $order)));
    }

    public function test_order_list_shows_shipped_state_per_seller_within_a_shared_order(): void
    {
        $seller = User::factory()->admin()->create();
        $otherSeller = User::factory()->admin()->create();
        $order = $this->createOrderContainingSellersBook($seller, User::factory()->create(), ['shipped_at' => '2026-09-01 10:30:00']);
        $this->addItemToOrder($order, $otherSeller);
        $shipUrl = route('shop.orders.ship', $order);

        $sellersPage = $this->actingAs($seller)->get('/shop/orders');
        $otherSellersPage = $this->actingAs($otherSeller)->get('/shop/orders');

        $sellersPage->assertSee('発送済み（2026-09-01 10:30）');
        $sellersPage->assertDontSee($shipUrl, false);
        $otherSellersPage->assertDontSee('発送済み');
        $otherSellersPage->assertSee($shipUrl, false);
    }

    public function test_shipped_mail_lists_only_the_given_items_and_their_total(): void
    {
        $seller = User::factory()->admin()->create(['name' => '猫書房']);
        $buyer = User::factory()->create(['name' => '購入太郎']);
        $order = $this->createOrderContainingSellersBook($seller, $buyer, ['title' => '吾輩は猫である', 'price' => 1200, 'quantity' => 2, 'shipped_at' => '2026-09-01 10:30:00']);
        $this->addItemToOrder($order, User::factory()->admin()->create(), ['title' => '他店の本']);

        $mailable = new OrderShippedMail($order, $order->items()->where('seller_id', $seller->id)->get());

        $mailable->assertHasSubject("ご注文の商品を発送しました（注文 #{$order->id}）");
        $mailable->assertSeeInHtml('購入太郎');
        $mailable->assertSeeInHtml('猫書房');
        $mailable->assertSeeInHtml('吾輩は猫である × 2');
        $mailable->assertDontSeeInHtml('他店の本');
        $mailable->assertSeeInHtml('¥2,400');
        $mailable->assertSeeInHtml('2026-09-01 10:30');
    }

    public function test_placing_an_order_snapshots_the_sellers_id_on_the_order_item(): void
    {
        $seller = User::factory()->admin()->create();
        $buyer = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $seller->id, 'is_for_sale' => true, 'price' => 1000]);
        Stock::factory()->create(['book_id' => $book->id, 'stock' => 5]);

        $this->actingAs($buyer)->post('/cart/items', ['book_id' => $book->id, 'quantity' => 1]);
        $this->actingAs($buyer)->post('/checkout');

        $this->assertDatabaseHas('order_items', [
            'book_id' => $book->id,
            'seller_id' => $seller->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $itemAttributes
     */
    private function createOrderContainingSellersBook(User $seller, User $buyer, array $itemAttributes = []): Order
    {
        $order = Order::factory()->create(['user_id' => $buyer->id]);
        $this->addItemToOrder($order, $seller, $itemAttributes);

        return $order->load('items');
    }

    /**
     * @param  array<string, mixed>  $itemAttributes
     */
    private function addItemToOrder(Order $order, User $seller, array $itemAttributes = []): OrderItem
    {
        $book = Book::factory()->create(['user_id' => $seller->id]);

        return OrderItem::factory()->create([
            'order_id' => $order->id,
            'book_id' => $book->id,
            'seller_id' => $seller->id,
            'title' => $book->title,
            ...$itemAttributes,
        ]);
    }
}
