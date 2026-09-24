<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

/**
 * メールアドレス確認リンクの処理を行うコントローラー。
 */
class VerifyEmailController extends Controller
{
    /**
     * ログイン中のユーザーのメールアドレスを確認済みにする。
     *
     * すでに確認済みの場合は何もせずリダイレクトする。
     *
     * @param  EmailVerificationRequest  $request  署名付きの確認リンクを検証済みのリクエスト
     * @return RedirectResponse 入荷本一覧（確認済みを示すクエリ付き）へのリダイレクト
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('book.new', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(route('book.new', absolute: false).'?verified=1');
    }
}
