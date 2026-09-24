<?php

namespace App\Livewire\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * ログアウト処理を行うLivewire用のアクション。
 */
class Logout
{
    /**
     * ログイン中のユーザーをログアウトし、セッションを破棄してCSRFトークンを再生成する。
     */
    public function __invoke(): void
    {
        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();
    }
}
