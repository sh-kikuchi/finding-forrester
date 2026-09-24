<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 本屋（ストア）画面のコントローラー。
 */
class StoreController extends Controller
{
    /**
     * 販売対象の本を一覧表示する。
     *
     * クエリ文字列の `type` が指定されている場合は、そのジャンルで絞り込む。
     * 商品詳細ページは持たないため、一覧のカード上で購入操作まで完結させる。
     *
     * @param  Request  $request  クエリ文字列 `type`（ジャンル）を受け取るHTTPリクエスト
     * @return View 本屋一覧画面（本一覧・ジャンル一覧・選択中のジャンルを渡す）
     */
    public function index(Request $request): View
    {
        $type = $request->string('type')->value() ?: null;

        $books = Book::query()
            ->with('stock')
            ->where('is_for_sale', true)
            ->when($type, fn ($query) => $query->where('type', $type))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->simplePaginate(12)
            ->withQueryString();

        $types = StoreBookRequest::TYPES;

        return view('store.index', compact('books', 'types', 'type'));
    }
}
