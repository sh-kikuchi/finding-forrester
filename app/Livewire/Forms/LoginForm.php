<?php

namespace App\Livewire\Forms;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

/**
 * ログインフォームの入力値と認証処理を持つLivewireのフォームオブジェクト。
 *
 * 同じメールアドレス・IPからの認証失敗が5回を超えると、一定時間ログインを拒否する。
 */
class LoginForm extends Form
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * 入力された認証情報でログインを試みる。
     *
     * 失敗時は試行回数を記録し、成功時は記録をクリアする。
     *
     * @throws ValidationException 認証に失敗した場合、または試行回数の上限を超えている場合
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only(['email', 'password']), $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * 認証の試行回数が上限を超えていないことを確認する。
     *
     * @throws ValidationException 上限を超えている場合（ロックアウトイベントも発行する）
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * 認証の試行回数を制限するためのキーを返す。
     *
     * @return string メールアドレス（小文字）とIPアドレスから作ったキー
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
