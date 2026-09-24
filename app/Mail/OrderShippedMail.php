<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * 1人の出品者が注文の一部（自分の本）を発送したことを購入者へ知らせるメール。
 *
 * 同じ注文に複数の出品者の本が含まれる場合は、出品者ごとに別のメールになる。
 * キュー経由で送る前提（`Mail::to(...)->queue(...)`）。Order と OrderItem はIDだけが
 * シリアライズされ、ワーカー実行時に再取得される。
 */
class OrderShippedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Order  $order  発送対象の注文
     * @param  Collection<int, OrderItem>  $items  今回発送した出品者の商品（1件以上）
     */
    public function __construct(public Order $order, public Collection $items)
    {
        $this->order->loadMissing('user');
        $this->items->loadMissing('seller');
    }

    /**
     * メールの件名などのエンベロープを返す。
     *
     * @return Envelope 件名に注文番号を含むエンベロープ
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('ご注文の商品を発送しました（注文 #:id）', ['id' => $this->order->id]),
        );
    }

    /**
     * メール本文（Markdown）と、ビューへ渡す変数を返す。
     *
     * @return Content ビュー`mail.order-shipped`に、出品者名・発送日時・合計金額を渡すContent
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.order-shipped',
            with: [
                'sellerName' => $this->items->first()->seller->name,
                'shippedAt' => $this->items->first()->shipped_at,
                'total' => $this->items->sum(fn (OrderItem $item): int => $item->price * $item->quantity),
            ],
        );
    }
}
