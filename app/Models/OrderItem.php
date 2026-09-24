<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 注文に含まれる1商品。購入時点のタイトル・価格・出品者を保持するスナップショット。
 *
 * `shipped_at` は、出品者がこの商品を発送した日時（未発送は null）。
 * 発送は「注文 × 出品者」の単位で行うため、同じ注文・同じ出品者の商品は同じ日時になる。
 */
#[Fillable(['order_id', 'book_id', 'seller_id', 'title', 'price', 'quantity'])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * 属性のキャスト定義を返す。
     *
     * @return array<string, string> 属性名 => キャスト型
     */
    protected function casts(): array
    {
        return [
            'shipped_at' => 'datetime',
        ];
    }

    /**
     * この商品が属する注文。
     *
     * @return BelongsTo 対象のOrder
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * 購入された本。本が削除された場合は null になる。
     *
     * @return BelongsTo 対象のBook
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * 購入時点で本を出品していた管理者（店舗）。本が削除されても残るスナップショット。
     *
     * @return BelongsTo 出品者のUser
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
