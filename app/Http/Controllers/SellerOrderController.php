<?php

namespace App\Http\Controllers;

use App\Mail\OrderShippedMail;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * 出品者（管理者）が、自分の本がどの注文で・誰に購入されたかを確認し、発送するコントローラー。
 *
 * ログイン必須（authミドルウェア配下）。`OrderItem.seller_id`（購入時点の出品者IDのスナップショット）
 * で絞り込むため、購入後に本が削除されても紐付けは失われない。
 */
class SellerOrderController extends Controller
{
    /**
     * ログイン中の出品者の本を含む注文を、注文単位で一覧表示する。
     *
     * 各注文には、ログイン中の出品者の商品だけを読み込む（他の出品者の商品は含めない）。
     *
     * @return View 注文一覧（新しい順、購入者・自分の商品を含む）を渡す画面
     */
    public function index(): View
    {
        $sellerId = Auth::id();

        $orders = Order::whereHas('items', fn (Builder $query) => $query->where('seller_id', $sellerId))
            ->with([
                'user',
                'items' => fn (HasMany $query) => $query->where('seller_id', $sellerId)->orderBy('id'),
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->simplePaginate(15);

        return view('seller.orders.index', compact('orders'));
    }

    /**
     * 注文のうち、ログイン中の出品者の商品をまとめて発送済みにし、購入者へ発送メールをキュー経由で送る。
     *
     * 発送の単位は「注文 × 出品者」。同じ出品者の商品は1通のメールにまとまり、同じ注文の
     * 別の出品者の商品には影響しない。自分の本を含む注文のみ操作できる（OrderPolicy::ship）。
     * 二重クリックや同時リクエストでメールが二重送信されないよう、`shipped_at IS NULL` の行を
     * 更新できた場合に限って送る。発送の取り消しは提供しない。
     *
     * @param  Order  $order  発送する注文
     * @return RedirectResponse 受注一覧へのリダイレクト
     */
    public function ship(Order $order): RedirectResponse
    {
        Gate::authorize('ship', $order);

        $shipped = $order->items()
            ->where('seller_id', Auth::id())
            ->whereNull('shipped_at')
            ->update(['shipped_at' => now()]);

        if ($shipped === 0) {
            return back()->with('status', __('この注文はすでに発送済みです。'));
        }

        $items = $order->items()->where('seller_id', Auth::id())->orderBy('id')->get();

        Mail::to($order->user)->queue(new OrderShippedMail($order, $items));

        return back()->with('status', __('発送メールを送信キューに登録しました。'));
    }
}
