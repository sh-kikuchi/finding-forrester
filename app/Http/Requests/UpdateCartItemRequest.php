<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * カート内の本の数量を変更する際の入力を検証するリクエスト。
 *
 * ゲストも利用できるため、認可は行わない。
 */
class UpdateCartItemRequest extends FormRequest
{
    /**
     * このリクエストを実行できるかどうか。
     *
     * @return bool 常に true（ゲストも利用できる）
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
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
