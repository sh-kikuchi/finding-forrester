<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 購入者（個人ユーザー）の注文。商品は OrderItem が持つ。
 *
 * 発送の状態は注文ではなく、注文商品（OrderItem）の `shipped_at` で管理する。
 */
#[Fillable(['user_id'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * この注文をした購入者。
     *
     * @return BelongsTo 購入者のUser
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この注文に含まれる注文商品。
     *
     * @return HasMany OrderItemの一覧
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * 注文商品の小計（価格 × 数量）の合計金額を返す。
     *
     * N+1を避けるため、呼び出し前に `items` を読み込んでおくこと。
     * `items` を出品者で絞り込んで読み込んだ場合は、その出品者分の小計になる。
     *
     * @return int 合計金額（円）
     */
    public function total(): int
    {
        return $this->items->sum(fn (OrderItem $item): int => $item->price * $item->quantity);
    }
}
