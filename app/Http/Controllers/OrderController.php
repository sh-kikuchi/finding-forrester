<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * 注文履歴画面のコントローラー。
 *
 * ログイン必須（authミドルウェア配下）。ログイン中のユーザー自身の注文のみを扱う。
 */
class OrderController extends Controller
{
    /**
     * ログイン中のユーザーの注文履歴を一覧表示する。
     *
     * @return View 注文一覧（新しい順）を渡す画面
     */
    public function index(): View
    {
        $orders = Order::where('user_id', Auth::id())
            ->with('items')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->simplePaginate(10);

        return view('orders.index', compact('orders'));
    }

    /**
     * 注文の詳細を表示する。
     *
     * ログイン中のユーザー自身の注文でない場合は403を返す。
     *
     * @param  Order  $order  ルートモデルバインディングで解決された対象の注文
     * @return View 注文詳細（注文商品一覧・合計金額）を渡す画面
     */
    public function show(Order $order): View
    {
        abort_unless($order->user_id === Auth::id(), 403);

        $order->load('items');

        return view('orders.show', compact('order'));
    }
}
