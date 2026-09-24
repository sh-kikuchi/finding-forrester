<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\Book;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * カート画面のコントローラー。
 *
 * セッションに保持するカート（book_id => 数量）の表示・追加・数量変更・削除を、
 * CartService経由で行う。ゲストでも利用できる。
 */
class CartController extends Controller
{
    /**
     * @param  CartService  $cart  セッションカートを操作するService
     */
    public function __construct(private readonly CartService $cart) {}

    /**
     * カートの中身を表示する。
     *
     * @return View カート内の本一覧・数量・合計金額を渡すカート画面
     */
    public function index(): View
    {
        return view('cart.index', [
            'books' => $this->cart->books(),
            'items' => $this->cart->items(),
            'total' => $this->cart->total(),
        ]);
    }

    /**
     * カートに本を追加する。
     *
     * @param  AddCartItemRequest  $request  book_id・quantityを検証済みのリクエスト
     * @return RedirectResponse カート画面へのリダイレクト
     */
    public function store(AddCartItemRequest $request): RedirectResponse
    {
        $this->cart->add($request->integer('book_id'), $request->integer('quantity'));

        return redirect()->route('cart.index');
    }

    /**
     * カート内の本の数量を変更する。
     *
     * @param  UpdateCartItemRequest  $request  変更後のquantityを検証済みのリクエスト
     * @param  Book  $book  ルートモデルバインディングで解決された対象の本
     * @return RedirectResponse カート画面へのリダイレクト
     */
    public function update(UpdateCartItemRequest $request, Book $book): RedirectResponse
    {
        $this->cart->updateQuantity($book->id, $request->integer('quantity'));

        return redirect()->route('cart.index');
    }

    /**
     * カートから本を削除する。
     *
     * @param  Book  $book  ルートモデルバインディングで解決された対象の本
     * @return RedirectResponse カート画面へのリダイレクト
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->cart->remove($book->id);

        return redirect()->route('cart.index');
    }
}
