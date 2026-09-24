<?php

namespace App\Http\Controllers;

use App\Actions\PlaceOrderAction;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * チェックアウト画面のコントローラー。
 *
 * ログイン必須（authミドルウェア配下）。未ログインでアクセスした場合はログイン画面へ
 * リダイレクトされ、ログイン成功後はLaravel標準のintended URL機構でこの画面へ戻る。
 */
class CheckoutController extends Controller
{
    /**
     * @param  CartService  $cart  セッションカートを操作するService
     */
    public function __construct(private readonly CartService $cart) {}

    /**
     * チェックアウト画面（カート内容の確認）を表示する。
     *
     * カートが空の場合は注文確定へ進ませず、カート画面へ戻す。
     *
     * @return View|RedirectResponse チェックアウト画面、またはカート画面へのリダイレクト
     */
    public function show(): View|RedirectResponse
    {
        if ($this->cart->items() === []) {
            return redirect()->route('cart.index')->with('status', __('カートが空です。'));
        }

        return view('checkout.index', [
            'books' => $this->cart->books(),
            'items' => $this->cart->items(),
            'total' => $this->cart->total(),
        ]);
    }

    /**
     * 注文を確定する。
     *
     * カートの中身をもとに PlaceOrderAction で注文・注文商品を作成し、
     * 成功したらセッションのカートを空にして注文詳細画面へリダイレクトする。
     *
     * @param  PlaceOrderAction  $placeOrder  在庫確認・価格確認・注文作成をトランザクションで行うAction
     * @return RedirectResponse 注文詳細画面、またはカートが空の場合はカート画面へのリダイレクト
     */
    public function store(PlaceOrderAction $placeOrder): RedirectResponse
    {
        $items = $this->cart->items();

        if ($items === []) {
            return redirect()->route('cart.index')->with('status', __('カートが空です。'));
        }

        $order = $placeOrder->handle(Auth::user(), $items);

        $this->cart->clear();

        return redirect()->route('orders.show', $order)->with('status', __('ご注文ありがとうございました。'));
    }
}
