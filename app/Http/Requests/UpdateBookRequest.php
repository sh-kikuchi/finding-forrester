<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 本の情報更新時の入力を検証するリクエスト。
 *
 * 「この本の所有者かどうか」は、コントローラー側で`BookPolicy`が判定する。
 */
class UpdateBookRequest extends FormRequest
{
    /**
     * このリクエストを実行できるかどうか。
     *
     * @return bool 常に true（認可はルートのミドルウェアと Policy で行う）
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
            'type' => ['required', 'string', Rule::in(StoreBookRequest::TYPES)],
            'stock' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'integer', 'min:0'],
            'is_for_sale' => ['boolean'],
        ];
    }
}
