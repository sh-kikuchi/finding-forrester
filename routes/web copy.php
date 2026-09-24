<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// 書籍マーケットプレイス: ログイン後ページ
Route::middleware('auth')->group(function () {
    Route::get('/new', [BookController::class, 'new'])->name('book.new');
    Route::match(['get', 'post'], '/book/search', [BookController::class, 'search'])->name('search');
    Route::match(['get', 'post'], '/book/create', [BookController::class, 'create'])->name('book.create');
    Route::post('/book/create/from-google', [BookController::class, 'createFromGoogle'])->name('book.create.fromGoogle');
    Route::get('/book/{book}', [BookController::class, 'show'])->name('book.show');
    Route::get('/book/{book}/edit', [BookController::class, 'edit'])->name('book.edit');
    Route::put('/book/{book}/update', [BookController::class, 'update'])->name('book.update');
    Route::delete('/book/{book}/delete', [BookController::class, 'destroy'])->name('book.destroy');

    Route::get('/shop/edit', [UserController::class, 'edit'])->name('shop.edit');
    Route::put('/shop/update', [UserController::class, 'update'])->name('shop.update');
});

// shop.show の {shop} ワイルドカードが /shop/edit を飲み込まないよう、auth グループより後ろに定義する
Route::get('/shop/{shop}', [UserController::class, 'show'])->name('shop.show');

require __DIR__.'/auth.php';
