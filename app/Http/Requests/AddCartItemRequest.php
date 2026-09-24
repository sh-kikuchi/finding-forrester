<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * カートへ本を追加する際の入力を検証するリクエスト。
 *
 * ゲストも利用できるため、認可は行わない。
 */
class AddCartItemRequest extends FormRequest
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
     * `book_id` は、販売対象（`is_for_sale`）の本のみ受け付ける。
     *
     * @return array<string, ValidationRule|array<mixed>|string> 入力項目名 => ルール
     */
    public function rules(): array
    {
        return [
            'book_id' => [
                'required',
                'integer',
                Rule::exists('books', 'id')->where(fn ($query) => $query->where('is_for_sale', true)),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
