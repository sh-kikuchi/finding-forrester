<?php

namespace App\Actions;

use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Stock;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * カートの中身から注文を確定するAction。
 *
 * 在庫確認・価格確認・注文/注文商品の作成・在庫更新を1つのトランザクションにまとめ、
 * 途中で失敗した場合は何も残さずロールバックする。同時注文による売り越しを防ぐため、
 * 在庫行は更新前に排他ロックする。
 */
class PlaceOrderAction
{
    /**
     * カートの中身から注文を確定する。
     *
     * 注文商品には、購入時点のタイトル・価格・出品者（`seller_id`）を記録し、在庫を減らす。
     *
     * @param  Authenticatable  $user  注文を確定するログイン中のユーザー
     * @param  array<int, int>  $cartItems  book_id => 数量
     * @return Order 作成した注文
     *
     * @throws ValidationException 販売対象外、または在庫不足の本が含まれる場合
     */
    public function handle(Authenticatable $user, array $cartItems): Order
    {
        return DB::transaction(function () use ($user, $cartItems): Order {
            $order = Order::create(['user_id' => $user->getAuthIdentifier()]);

            foreach ($cartItems as $bookId => $quantity) {
                $book = Book::findOrFail($bookId);

                if (! $book->is_for_sale) {
                    throw ValidationException::withMessages([
                        'cart' => __('「:title」は現在販売対象外です。', ['title' => $book->title]),
                    ]);
                }

                // 同時注文による売り越しを防ぐため、在庫行をロックしてから確認・更新する。
                $stock = Stock::where('book_id', $book->id)->lockForUpdate()->first();
                $available = $stock?->stock ?? 0;

                if ($quantity > $available) {
                    throw ValidationException::withMessages([
                        'cart' => __('「:title」の在庫が不足しています。', ['title' => $book->title]),
                    ]);
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'book_id' => $book->id,
                    'seller_id' => $book->user_id,
                    'title' => $book->title,
                    'price' => $book->price,
                    'quantity' => $quantity,
                ]);

                $stock->decrement('stock', $quantity);
            }

            return $order;
        });
    }
}
