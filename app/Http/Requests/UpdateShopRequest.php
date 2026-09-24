<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ショップ情報（ログイン中の管理者自身のプロフィール）の更新時の入力を検証するリクエスト。
 */
class UpdateShopRequest extends FormRequest
{
    /**
     * このリクエストを実行できるかどうか。
     *
     * @return bool 常に true（認可はルートのミドルウェアで行う）
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * リクエストに適用するバリデーションルールを返す。
     *
     * メールアドレスは、ログイン中のユーザー自身のものを除いて重複を禁止する。
     * `newPassword` は空欄の場合、パスワードを変更しない。
     *
     * @return array<string, ValidationRule|array<mixed>|string> 入力項目名 => ルール
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'newPassword' => ['nullable', 'string', 'min:8'],
            'address' => ['nullable', 'string', 'max:255'],
            'tel' => ['nullable', 'string', 'max:255'],
            'time' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:10240'],
        ];
    }
}
