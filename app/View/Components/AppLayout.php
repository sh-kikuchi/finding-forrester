<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * ログイン後の画面で使う共通レイアウト（`<x-app-layout>`）。
 */
class AppLayout extends Component
{
    /**
     * コンポーネントを表すビューを返す。
     *
     * @return View レイアウトのビュー`layouts.app`
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
