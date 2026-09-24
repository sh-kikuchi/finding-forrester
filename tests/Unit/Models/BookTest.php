<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use Tests\TestCase;

class BookTest extends TestCase
{
    public function test_image_url_is_null_when_no_image_is_set(): void
    {
        $book = new Book(['image' => null]);

        $this->assertNull($book->image_url);
    }

    public function test_image_url_resolves_a_local_filename_to_the_bookimg_asset_path(): void
    {
        $book = new Book(['image' => 'cover.jpg']);

        $this->assertSame(asset('image/cover.jpg'), $book->image_url);
    }

    public function test_image_url_returns_an_external_url_unchanged(): void
    {
        $url = 'https://books.google.com/books/content?id=abc123';
        $book = new Book(['image' => $url]);

        $this->assertSame($url, $book->image_url);
    }
}
