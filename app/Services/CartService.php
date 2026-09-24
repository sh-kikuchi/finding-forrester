<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * セッションカート（book_id => 数量）の読み書きを一元管理するService。
 *
 * DBテーブルは持たず、常にセッションを正とする。カート内の本の情報（タイトル・価格等）は
 * 一切コピーせず、表示のたびにDBから最新の状態を取得する。
 */
class CartService
{
    private const SESSION_KEY = 'cart';

    /**
     * @param  Session  $session  カートを保持するセッション
     */
    public function __construct(private readonly Session $session) {}

    /**
     * カートの中身を取得する。
     *
     * @return array<int, int> book_id => 数量
     */
    public function items(): array
    {
        return $this->session->get(self::SESSION_KEY, []);
    }

    /**
     * カートに本を追加する。既にカートにある場合は数量を加算する。
     *
     * @param  int  $bookId  追加する本のID
     * @param  int  $quantity  追加する数量
     *
     * @throws ValidationException 在庫数を超える場合
     */
    public function add(int $bookId, int $quantity): void
    {
        $book = Book::findOrFail($bookId);

        $cart = $this->items();
        $requested = ($cart[$bookId] ?? 0) + $quantity;

        $this->assertWithinStock($book, $requested);

        $cart[$bookId] = $requested;
        $this->session->put(self::SESSION_KEY, $cart);
    }

    /**
     * カート内の本の数量を、指定の値に変更する（加算ではなく置き換え）。
     *
     * @param  int  $bookId  対象の本のID
     * @param  int  $quantity  変更後の数量
     *
     * @throws ValidationException 在庫数を超える場合
     */
    public function updateQuantity(int $bookId, int $quantity): void
    {
        $cart = $this->items();

        abort_unless(array_key_exists($bookId, $cart), 404);

        $book = Book::findOrFail($bookId);
        $this->assertWithinStock($book, $quantity);

        $cart[$bookId] = $quantity;
        $this->session->put(self::SESSION_KEY, $cart);
    }

    /**
     * カートから本を削除する。
     *
     * @param  int  $bookId  対象の本のID
     */
    public function remove(int $bookId): void
    {
        $cart = $this->items();
        unset($cart[$bookId]);
        $this->session->put(self::SESSION_KEY, $cart);
    }

    /**
     * カートを空にする。
     */
    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /**
     * カート内の本をDBから取得する（在庫を含む）。
     *
     * @return Collection<int, Book> book_id をキーにしたBookのコレクション
     */
    public function books(): Collection
    {
        return Book::with('stock')->whereIn('id', array_keys($this->items()))->get()->keyBy('id');
    }

    /**
     * カートの合計金額を算出する。DBに存在しない本は計算に含めない。
     *
     * @return int 合計金額（円）
     */
    public function total(): int
    {
        $books = $this->books();

        return collect($this->items())
            ->filter(fn (int $quantity, int $bookId): bool => $books->has($bookId))
            ->sum(fn (int $quantity, int $bookId): int => $books[$bookId]->price * $quantity);
    }

    /**
     * 指定の数量が在庫数以内であることを確認する。
     *
     * @param  Book  $book  対象の本
     * @param  int  $quantity  カートに入れたい（入れる）数量
     *
     * @throws ValidationException 在庫数を超える場合
     */
    private function assertWithinStock(Book $book, int $quantity): void
    {
        $available = $book->stock?->stock ?? 0;

        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => __('在庫数を超えています。'),
            ]);
        }
    }
}
