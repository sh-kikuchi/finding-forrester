<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * ログイン・登録などゲスト向け画面で使う共通レイアウト（`<x-guest-layout>`）。
 */
class GuestLayout extends Component
{
    /**
     * コンポーネントを表すビューを返す。
     *
     * @return View レイアウトのビュー`layouts.guest`
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
