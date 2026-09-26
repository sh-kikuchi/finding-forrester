<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SellerOrderController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// 入荷本一覧: 誰でも閲覧可能（ゲスト・個人ユーザー・管理者いずれもOK）
Route::get('/new', [BookController::class, 'new'])->name('book.new');

// 本のタイトル検索: 誰でも利用可能（管理者は自分の店の本だけが対象）
Route::get('/search', [BookController::class, 'search'])->name('search');

// 本屋・カート: ゲスト+個人ユーザーが利用可能。ログイン中の管理者は利用不可
Route::middleware('role:user')->group(function () {
    Route::get('/store', [StoreController::class, 'index'])->name('store.index');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/items', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/items/{book}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/items/{book}', [CartController::class, 'destroy'])->name('cart.destroy');
});

// 本の在庫管理・ショップ情報編集: 管理者（店舗）専用
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/book/google-search', [BookController::class, 'searchGoogle'])->name('book.searchGoogle');
    Route::match(['get', 'post'], '/book/create', [BookController::class, 'create'])->name('book.create');
    Route::post('/book/create/from-google', [BookController::class, 'createFromGoogle'])->name('book.create.fromGoogle');
    Route::get('/book/{book}', [BookController::class, 'show'])->name('book.show');
    Route::get('/book/{book}/edit', [BookController::class, 'edit'])->name('book.edit');
    Route::put('/book/{book}/update', [BookController::class, 'update'])->name('book.update');
    Route::delete('/book/{book}/delete', [BookController::class, 'destroy'])->name('book.destroy');

    Route::get('/shop/edit', [UserController::class, 'edit'])->name('shop.edit');
    Route::put('/shop/update', [UserController::class, 'update'])->name('shop.update');
    Route::get('/shop/orders', [SellerOrderController::class, 'index'])->name('shop.orders.index');
    Route::post('/shop/orders/{order}/ship', [SellerOrderController::class, 'ship'])->name('shop.orders.ship');
});

// チェックアウト・注文履歴: 個人ユーザー専用
Route::middleware(['auth', 'role:user'])->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
});

// shop.show の {shop} ワイルドカードが /shop/edit を飲み込まないよう、auth グループより後ろに定義する
Route::get('/shop/{shop}', [UserController::class, 'show'])->name('shop.show');

require __DIR__.'/auth.php';
