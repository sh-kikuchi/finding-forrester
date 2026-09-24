<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 本の新規登録時の入力を検証するリクエスト。
 *
 * ルート側の`role:admin`ミドルウェアで管理者に限定しているため、認可はここでは行わない。
 */
class StoreBookRequest extends FormRequest
{
    /**
     * 登録フォームで選択できる本のジャンル。
     *
     * @var array<int, string>
     */
    public const TYPES = ['未分類', 'ホラー', 'ミステリー', 'アクション', '恋愛', 'SF', '歴史', '自伝'];

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
     * @return array<string, ValidationRule|array<mixed>|string> 入力項目名 => ルール
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(self::TYPES)],
            'stock' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'integer', 'min:0'],
            'is_for_sale' => ['boolean'],
            'image' => ['nullable', 'image', 'max:10240'],
            'google_image_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
