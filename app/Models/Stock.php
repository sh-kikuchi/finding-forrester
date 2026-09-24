<?php

namespace App\Models;

use Database\Factories\StockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 本の在庫数。1冊の本につき1件。
 */
#[Fillable(['book_id', 'stock'])]
class Stock extends Model
{
    /** @use HasFactory<StockFactory> */
    use HasFactory;

    /**
     * この在庫が属する本。
     *
     * @return BelongsTo 対象のBook
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
