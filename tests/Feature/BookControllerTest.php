<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_the_book_stock_page(): void
    {
        $response = $this->get('/new');

        $response->assertOk();
    }

    public function test_an_individual_user_can_view_the_book_stock_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/new');

        $response->assertOk();
    }

    public function test_guest_sees_an_add_to_cart_button_on_the_book_stock_page(): void
    {
        $book = Book::factory()->create(['is_for_sale' => true, 'price' => 1200, 'created_at' => now()]);
        Stock::factory()->create(['book_id' => $book->id, 'stock' => 3]);

        $response = $this->get('/new');

        $response->assertOk();
        $response->assertSee(__('カートに追加'));
        $response->assertDontSee(__('編集'));
    }

    public function test_individual_user_does_not_see_edit_or_delete_buttons_even_if_ids_coincide(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id, 'is_for_sale' => true, 'created_at' => now()]);
        Stock::factory()->create(['book_id' => $book->id, 'stock' => 3]);

        $response = $this->actingAs($user)->get('/new');

        $response->assertOk();
        $response->assertSee(__('カートに追加'));
        $response->assertDontSee(__('編集'));
        $response->assertDontSee(__('削除'));
    }

    public function test_admin_sees_edit_and_delete_buttons_for_their_own_book_but_no_add_to_cart_button(): void
    {
        $admin = User::factory()->admin()->create();
        Book::factory()->create(['user_id' => $admin->id, 'created_at' => now()]);

        $response = $this->actingAs($admin)->get('/new');

        $response->assertOk();
        $response->assertSee(__('編集'));
        $response->assertSee(__('削除'));
        $response->assertDontSee(__('カートに追加'));
    }

    public function test_add_to_cart_button_is_disabled_for_a_book_not_for_sale(): void
    {
        Book::factory()->create(['is_for_sale' => false, 'created_at' => now()]);

        $response = $this->get('/new');

        $response->assertOk();
        $response->assertSee(__('販売対象外'));
    }

    public function test_books_without_an_image_show_the_placeholder_on_the_book_stock_page(): void
    {
        $user = User::factory()->admin()->create();
        Book::factory()->create(['image' => null, 'created_at' => now()]);

        $response = $this->actingAs($user)->get('/new');

        $response->assertOk();
        $response->assertSee('画像が設定されていません');
    }

    public function test_books_with_a_google_thumbnail_url_render_it_directly(): void
    {
        $user = User::factory()->admin()->create();
        Book::factory()->create(['image' => 'https://books.google.com/books/content?id=abc123', 'created_at' => now()]);

        $response = $this->actingAs($user)->get('/new');

        $response->assertOk();
        $response->assertSee('https://books.google.com/books/content?id=abc123', false);
    }

    public function test_new_page_only_lists_books_added_within_the_last_month(): void
    {
        $this->travelTo(now());
        $user = User::factory()->admin()->create();

        $recentBook = Book::factory()->create(['created_at' => now()->subDays(3)]);
        Book::factory()->create(['created_at' => now()->subMonths(2)]);

        $response = $this->actingAs($user)->get('/new');

        $response->assertOk();
        $response->assertViewHas('books', function ($books) use ($recentBook) {
            return $books->pluck('id')->all() === [$recentBook->id];
        });
    }

    public function test_guest_can_search_books_by_title_and_sees_shop_link_and_add_to_cart_button(): void
    {
        $matching = Book::factory()->create(['title' => 'Finding Forrester']);
        Stock::factory()->create(['book_id' => $matching->id, 'stock' => 3]);
        Book::factory()->create(['title' => 'Unrelated Title']);

        $response = $this->get('/search?q=Forrester');

        $response->assertViewHas('books', function ($books) use ($matching) {
            return $books->pluck('id')->all() === [$matching->id];
        });
        $response->assertSee('Finding Forrester');
        $response->assertDontSee('Unrelated Title');
        $response->assertSee(route('shop.show', ['shop' => $matching->user_id]), false);
        $response->assertSee(__('カートに追加'));
    }

    public function test_an_individual_user_can_search_books_by_title(): void
    {
        $user = User::factory()->create();
        $matching = Book::factory()->create(['title' => 'Finding Forrester']);
        Stock::factory()->create(['book_id' => $matching->id, 'stock' => 3]);

        $response = $this->actingAs($user)->get('/search?q=Forrester');

        $response->assertSee('Finding Forrester');
        $response->assertSee(__('カートに追加'));
    }

    public function test_site_search_hides_books_that_are_not_for_sale_from_non_admins(): void
    {
        Book::factory()->create(['title' => 'Finding Forrester', 'is_for_sale' => false]);

        $response = $this->get('/search?q=Forrester');

        $response->assertDontSee('Finding Forrester');
        $response->assertSee(__('該当する本が見つかりませんでした。'));
    }

    public function test_admin_site_search_lists_only_their_own_books_without_purchase_controls(): void
    {
        $admin = User::factory()->admin()->create();
        $ownBook = Book::factory()->create(['user_id' => $admin->id, 'title' => 'Finding Forrester Own']);
        Book::factory()->create(['title' => 'Finding Forrester Other Shop']);

        $response = $this->actingAs($admin)->get('/search?q=Forrester');

        $response->assertViewHas('books', function ($books) use ($ownBook) {
            return $books->pluck('id')->all() === [$ownBook->id];
        });
        $response->assertSee(__('編集'));
        $response->assertDontSee(__('カートに追加'));
        $response->assertDontSee(__('取扱店舗'));
    }

    public function test_admin_site_search_includes_their_own_books_that_are_not_for_sale(): void
    {
        $admin = User::factory()->admin()->create();
        $ownBook = Book::factory()->create(['user_id' => $admin->id, 'title' => 'Finding Forrester', 'is_for_sale' => false]);

        $response = $this->actingAs($admin)->get('/search?q=Forrester');

        $response->assertViewHas('books', function ($books) use ($ownBook) {
            return $books->pluck('id')->all() === [$ownBook->id];
        });
    }

    public function test_site_search_treats_like_wildcards_in_the_keyword_literally(): void
    {
        $literal = Book::factory()->create(['title' => '100% Pure']);
        Book::factory()->create(['title' => '1000 Pure']);

        $response = $this->get('/search?q='.urlencode('100%'));

        $response->assertViewHas('books', function ($books) use ($literal) {
            return $books->pluck('id')->all() === [$literal->id];
        });
    }

    public function test_site_search_pagination_links_keep_the_keyword(): void
    {
        Book::factory()->count(10)->sequence(fn ($sequence) => ['title' => 'Forrester '.$sequence->index])->create();

        $response = $this->get('/search?q=Forrester');

        $response->assertSee('q=Forrester&amp;page=2', false);
    }

    public function test_site_search_shows_a_message_when_nothing_matches(): void
    {
        Book::factory()->create(['title' => 'Unrelated Title']);

        $response = $this->get('/search?q=Forrester');

        $response->assertSee(__('該当する本が見つかりませんでした。'));
    }

    public function test_site_search_without_a_keyword_shows_only_the_form(): void
    {
        Book::factory()->create(['title' => 'Finding Forrester']);

        $response = $this->get('/search');

        $response->assertViewMissing('books');
        $response->assertDontSee('Finding Forrester');
        $response->assertDontSee(__('該当する本が見つかりませんでした。'));
    }

    public function test_site_search_escapes_the_keyword_in_the_page(): void
    {
        $response = $this->get('/search?q='.urlencode('<script>alert(1)</script>'));

        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_google_search_tab_is_hidden_from_non_admins(): void
    {
        $response = $this->get('/search');

        $response->assertDontSee(__('GoogleBookから本を検索'));
        $response->assertDontSee(route('book.searchGoogle'), false);
    }

    public function test_google_search_tab_is_shown_to_admins(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/search');

        $response->assertSee(__('GoogleBookから本を検索'));
        $response->assertSee(route('book.searchGoogle'), false);
    }

    public function test_guest_is_redirected_to_login_when_searching_google_books(): void
    {
        $response = $this->get(route('book.searchGoogle', ['q' => 'Some Book']));

        $response->assertRedirect(route('login'));
    }

    public function test_an_individual_user_is_forbidden_from_searching_google_books(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('book.searchGoogle', ['q' => 'Some Book']));

        $response->assertForbidden();
    }

    public function test_google_search_without_a_keyword_does_not_call_the_api(): void
    {
        $admin = User::factory()->admin()->create();
        Http::preventStrayRequests();

        $response = $this->actingAs($admin)->get(route('book.searchGoogle'));

        $response->assertViewMissing('json_decode');
    }

    public function test_google_books_search_returns_api_results(): void
    {
        $user = User::factory()->admin()->create();
        Http::preventStrayRequests();
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    ['volumeInfo' => ['title' => 'Some Book', 'authors' => ['Some Author']]],
                ],
            ]),
        ]);

        $response = $this->actingAs($user)->get(route('book.searchGoogle', ['q' => 'Some Book']));

        $response->assertOk();
        $response->assertViewHas('json_decode', function ($json) {
            return $json['items'][0]['volumeInfo']['title'] === 'Some Book';
        });
        $response->assertSee('Some Book');
        $response->assertSee('Some Author');
        Http::assertSent(fn (ClientRequest $request) => str_starts_with($request->url(), 'https://www.googleapis.com/books/v1/volumes')
            && $request['q'] === 'Some Book');
    }

    public function test_google_books_search_includes_the_configured_api_key(): void
    {
        $user = User::factory()->admin()->create();
        config(['services.google_books.key' => 'test-api-key']);
        Http::preventStrayRequests();
        Http::fake(['https://www.googleapis.com/books/v1/volumes*' => Http::response(['items' => []])]);

        $this->actingAs($user)->get(route('book.searchGoogle', ['q' => 'Some Book']));

        Http::assertSent(fn (ClientRequest $request) => $request['key'] === 'test-api-key');
    }

    public function test_google_books_search_shows_a_friendly_message_when_the_api_errors(): void
    {
        $user = User::factory()->admin()->create();
        Http::preventStrayRequests();
        Http::fake(['https://www.googleapis.com/books/v1/volumes*' => Http::response(['error' => ['message' => 'Quota exceeded']], 429)]);

        $response = $this->actingAs($user)->get(route('book.searchGoogle', ['q' => 'Some Book']));

        $response->assertOk();
        $response->assertSee('Google Books検索でエラーが発生しました。しばらくしてから再度お試しください。');
    }

    public function test_google_books_search_shows_a_friendly_message_when_the_connection_fails(): void
    {
        $user = User::factory()->admin()->create();
        Http::preventStrayRequests();
        Http::fake(function () {
            throw new ConnectionException('Could not resolve host');
        });

        $response = $this->actingAs($user)->get(route('book.searchGoogle', ['q' => 'Some Book']));

        $response->assertOk();
        $response->assertSee('Google Booksへの接続に失敗しました。しばらくしてから再度お試しください。');
    }

    public function test_authenticated_users_see_a_register_button_on_google_search_results(): void
    {
        $user = User::factory()->admin()->create();
        Http::preventStrayRequests();
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    ['volumeInfo' => ['title' => 'Some Book', 'authors' => ['Some Author']]],
                ],
            ]),
        ]);

        $response = $this->actingAs($user)->get(route('book.searchGoogle', ['q' => 'Some Book']));

        $response->assertOk();
        $response->assertSee(route('book.create.fromGoogle'), false);
        $response->assertSee('value="Some Book"', false);
        $response->assertSee('value="Some Author"', false);
        $response->assertSee('この本を登録する');
    }

    public function test_guest_is_redirected_to_login_when_registering_a_book_from_google(): void
    {
        $response = $this->post('/book/create/from-google', [
            'title' => 'Some Book',
            'author' => 'Some Author',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_registering_a_book_from_google_requires_title_and_author(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post('/book/create/from-google', []);

        $response->assertSessionHasErrors(['title', 'author']);
    }

    public function test_registering_a_book_from_google_prefills_the_listing_form_with_defaults(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post('/book/create/from-google', [
            'title' => 'Some Book',
            'author' => 'Some Author',
        ]);

        $response->assertRedirect(route('book.create'));
        $response->assertSessionHas('_old_input', function (array $old) {
            return $old['title'] === 'Some Book'
                && $old['author'] === 'Some Author'
                && $old['type'] === '未分類'
                && $old['stock'] === 1;
        });
    }

    public function test_confirming_a_google_book_saves_it_with_the_default_genre_and_stock(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post('/book/create', [
            'title' => 'Some Book',
            'author' => 'Some Author',
            'type' => '未分類',
            'stock' => 1,
            'price' => 1000,
        ]);

        $response->assertRedirect(route('book.create'));

        $book = Book::sole();
        $this->assertSame('未分類', $book->type);
        $this->assertSame(1, Stock::where('book_id', $book->id)->value('stock'));
    }

    public function test_registering_a_book_from_google_carries_the_thumbnail_url_into_the_prefilled_form(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post('/book/create/from-google', [
            'title' => 'Some Book',
            'author' => 'Some Author',
            'image' => 'http://books.google.com/books/content?id=abc123',
        ]);

        $response->assertRedirect(route('book.create'));
        $response->assertSessionHas('_old_input', function (array $old) {
            return $old['google_image_url'] === 'https://books.google.com/books/content?id=abc123';
        });
    }

    public function test_confirming_a_google_book_with_a_thumbnail_saves_the_image_url(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->post('/book/create', [
            'title' => 'Some Book',
            'author' => 'Some Author',
            'type' => '未分類',
            'stock' => 1,
            'price' => 1000,
            'google_image_url' => 'https://books.google.com/books/content?id=abc123',
        ]);

        $book = Book::sole();
        $this->assertSame('https://books.google.com/books/content?id=abc123', $book->image);
    }

    public function test_uploaded_image_takes_precedence_over_the_google_thumbnail_url(): void
    {
        Storage::fake('bookimg');
        $user = User::factory()->admin()->create();
        $image = UploadedFile::fake()->create('cover.jpg', 10, 'image/jpeg');

        $this->actingAs($user)->post('/book/create', [
            'title' => 'Some Book',
            'author' => 'Some Author',
            'type' => '未分類',
            'stock' => 1,
            'price' => 1000,
            'image' => $image,
            'google_image_url' => 'https://books.google.com/books/content?id=abc123',
        ]);

        $book = Book::sole();
        $this->assertSame('cover.jpg', $book->image);
    }

    public function test_guest_is_redirected_to_login_when_opening_the_listing_form(): void
    {
        $response = $this->get('/book/create');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_the_listing_form(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get('/book/create');

        $response->assertOk()->assertViewIs('book.create');
    }

    public function test_listing_a_book_requires_title_author_type_and_stock(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post('/book/create', []);

        $response->assertSessionHasErrors(['title', 'author', 'type', 'stock']);
    }

    public function test_listing_a_book_rejects_a_genre_outside_the_allowed_list(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post('/book/create', [
            'title' => 'Title',
            'author' => 'Author',
            'type' => '存在しないジャンル',
            'stock' => 1,
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_valid_submission_creates_a_book_with_stock_and_redirects(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post('/book/create', [
            'title' => 'Finding Forrester',
            'author' => 'Mike Rich',
            'type' => 'SF',
            'stock' => 5,
            'price' => 1500,
        ]);

        $response->assertRedirect(route('book.create'));

        $book = Book::sole();
        $this->assertSame($user->id, $book->user_id);
        $this->assertSame('Finding Forrester', $book->title);
        $this->assertSame(5, Stock::where('book_id', $book->id)->value('stock'));
    }

    public function test_uploaded_image_is_stored_and_associated_with_the_book(): void
    {
        Storage::fake('bookimg');
        $user = User::factory()->admin()->create();
        $image = UploadedFile::fake()->create('cover.jpg', 10, 'image/jpeg');

        $this->actingAs($user)->post('/book/create', [
            'title' => 'Finding Forrester',
            'author' => 'Mike Rich',
            'type' => 'SF',
            'stock' => 5,
            'price' => 1500,
            'image' => $image,
        ]);

        $book = Book::sole();
        $this->assertSame('cover.jpg', $book->image);
        Storage::disk('bookimg')->assertExists('image/cover.jpg');
    }

    public function test_show_displays_the_books_details(): void
    {
        $user = User::factory()->admin()->create();
        $book = Book::factory()->create(['user_id' => $user->id, 'title' => 'Finding Forrester']);

        $response = $this->actingAs($user)->get("/book/{$book->id}");

        $response->assertOk();
        $response->assertViewHas('book', fn ($viewBook) => $viewBook->is($book));
        $response->assertSee('Finding Forrester');
    }

    public function test_updating_a_book_requires_title_author_type_and_stock(): void
    {
        $user = User::factory()->admin()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put("/book/{$book->id}/update", []);

        $response->assertSessionHasErrors(['title', 'author', 'type', 'stock']);
    }

    public function test_valid_update_persists_changes_and_redirects_to_show(): void
    {
        $user = User::factory()->admin()->create();
        $book = Book::factory()->create(['user_id' => $user->id, 'title' => 'Old Title']);
        Stock::factory()->create(['book_id' => $book->id, 'stock' => 3]);

        $response = $this->actingAs($user)->put("/book/{$book->id}/update", [
            'title' => 'New Title',
            'author' => 'New Author',
            'type' => 'ミステリー',
            'stock' => 10,
            'price' => 2000,
        ]);

        $response->assertRedirect(route('book.show', ['book' => $book]));
        $this->assertSame('New Title', $book->fresh()->title);
        $this->assertSame(10, $book->fresh()->stock->stock);
    }

    public function test_updating_a_book_without_existing_stock_creates_one(): void
    {
        $user = User::factory()->admin()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put("/book/{$book->id}/update", [
            'title' => $book->title,
            'author' => $book->author,
            'type' => $book->type,
            'stock' => 7,
            'price' => $book->price,
        ]);

        $response->assertRedirect(route('book.show', ['book' => $book]));
        $this->assertSame(7, $book->fresh()->stock->stock);
    }

    public function test_individual_users_cannot_access_book_management(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/book/create')->assertForbidden();
        $this->actingAs($user)->post('/book/create', [])->assertForbidden();
    }

    public function test_an_admin_cannot_view_another_admins_book(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $book = Book::factory()->create(['user_id' => $otherAdmin->id]);

        $this->actingAs($admin)->get("/book/{$book->id}")->assertForbidden();
    }

    public function test_an_admin_cannot_edit_another_admins_book(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $book = Book::factory()->create(['user_id' => $otherAdmin->id]);

        $this->actingAs($admin)->get("/book/{$book->id}/edit")->assertForbidden();
    }

    public function test_an_admin_cannot_update_another_admins_book(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $book = Book::factory()->create(['user_id' => $otherAdmin->id]);

        $response = $this->actingAs($admin)->put("/book/{$book->id}/update", [
            'title' => 'Hijacked',
            'author' => $book->author,
            'type' => $book->type,
            'stock' => 1,
            'price' => 1,
        ]);

        $response->assertForbidden();
        $this->assertNotSame('Hijacked', $book->fresh()->title);
    }

    public function test_an_admin_cannot_delete_another_admins_book(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $book = Book::factory()->create(['user_id' => $otherAdmin->id]);

        $this->actingAs($admin)->delete("/book/{$book->id}/delete")->assertForbidden();
        $this->assertModelExists($book);
    }
}
