<?php

namespace App\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 出品者（管理者）が登録する本。
 *
 * 在庫数は Stock が別テーブルで持つ。画像は外部URLまたはローカルのファイル名を保持する。
 */
#[Fillable(['user_id', 'title', 'author', 'type', 'image', 'price', 'is_for_sale'])]
class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    /**
     * 属性のキャスト定義を返す。
     *
     * @return array<string, string> 属性名 => キャスト型
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_for_sale' => 'boolean',
        ];
    }

    /**
     * この本を出品している管理者（店舗）。
     *
     * @return BelongsTo 出品者のUser
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この本の在庫。
     *
     * @return HasOne 在庫のStock
     */
    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class);
    }

    /**
     * `image` を表示用のURLに変換するアクセサ。
     *
     * 外部URLはそのまま返し、ローカルのファイル名は`bookimg`ディスク（`public/image/`）の
     * URLに解決する。画像がない場合は null を返す。
     *
     * @return Attribute 表示用URL（画像なしの場合は null）を返すアクセサ
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => match (true) {
                $this->image === null => null,
                str_starts_with($this->image, 'http://'), str_starts_with($this->image, 'https://') => $this->image,
                default => asset('image/'.$this->image),
            },
        );
    }
}
