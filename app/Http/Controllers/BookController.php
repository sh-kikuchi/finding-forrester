<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Stock;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * 本の登録・検索・詳細表示・編集・削除を扱うコントローラー。
 *
 * `new()`（入荷本一覧）のみ誰でも閲覧可能な公開画面で、それ以外は`auth`+`role:admin`
 * ミドルウェア配下（管理者/店舗ロール専用）。本屋（StoreController）とは異なり、
 * ここは出品者自身が自分の本棚を管理するための画面。
 * 「そもそも管理者かどうか」はroute側のmiddlewareが判定し、「この本の所有者かどうか」は
 * `BookPolicy`が判定する。
 */
class BookController extends Controller
{
    /**
     * 過去1ヶ月に登録された本を一覧表示する。
     *
     * 誰でも閲覧可能。管理者自身の本には`book-card`コンポーネント側で編集・削除ボタンを
     * 出し、それ以外（ゲスト・個人ユーザー・他の管理者の本を見る場合）はカート追加を出す。
     *
     * @return View 直近1ヶ月分の本一覧を渡す画面
     */
    public function new(): View
    {
        $books = Book::with('stock')
            ->whereDate('created_at', '>=', Carbon::today()->subMonth())
            ->get();

        return view('book.new', compact('books'));
    }

    /**
     * 本の登録フォームを表示する（GET）、または新しい本を登録する（POST）。
     *
     * 同一ルートでGET/POSTの両方を受けるため`StoreBookRequest`を型ヒントできず、
     * POST時のみ手動でバリデーションを実行している。
     *
     * @param  Request  $request  GET/POST両方を受け付けるリクエスト
     * @return RedirectResponse|View POST成功時は登録フォームへのリダイレクト、GET時はフォーム画面
     */
    public function create(Request $request): RedirectResponse|View
    {
        if ($request->isMethod('post')) {
            // The route accepts both GET and POST, so the request can't be type-hinted as
            // StoreBookRequest directly: that would run its validation on the GET request too.
            $validated = $request->validate((new StoreBookRequest)->rules());

            $imageName = null;

            if ($request->hasFile('image')) {
                $imageName = $request->file('image')->getClientOriginalName();
                $request->file('image')->storeAs('image', $imageName, 'bookimg');
            } elseif ($request->filled('google_image_url')) {
                $imageName = $validated['google_image_url'];
            }

            $book = Book::create([
                'user_id' => Auth::id(),
                'title' => $validated['title'],
                'author' => $validated['author'],
                'type' => $validated['type'],
                'image' => $imageName,
                'price' => $validated['price'],
                'is_for_sale' => $request->boolean('is_for_sale', true),
            ]);

            Stock::create([
                'book_id' => $book->id,
                'stock' => $validated['stock'],
            ]);

            return redirect()->route('book.create');
        }

        return view('book.create');
    }

    /**
     * Google Books検索結果の内容を、本の登録フォームにプリフィルする。
     *
     * @param  Request  $request  タイトル・著者・画像URL（任意）を含むリクエスト
     * @return RedirectResponse 入力値を保持したまま登録フォームへのリダイレクト
     */
    public function createFromGoogle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'url', 'max:2048'],
        ]);

        // Google Books often serves thumbnails over http://, which browsers block as mixed content on an https page.
        $imageUrl = isset($validated['image']) ? preg_replace('/^http:/', 'https:', $validated['image']) : null;

        return redirect()->route('book.create')->withInput([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'type' => '未分類',
            'stock' => 1,
            'google_image_url' => $imageUrl,
        ]);
    }

    /**
     * タイトルによるサイト内検索を行う。
     *
     * 誰でも利用可能。管理者は自分の店の本（販売対象外を含む）だけを、
     * それ以外（ゲスト・個人ユーザー）は販売対象の本だけを検索対象にする。
     *
     * @param  Request  $request  クエリ文字列 `q`（タイトルのキーワード）を受け取るHTTPリクエスト
     * @return View 検索画面（キーワードが空の場合は入力フォームのみ）
     */
    public function search(Request $request): View
    {
        $key = $request->string('q')->trim()->value();

        if ($key === '') {
            return view('book.search');
        }

        $user = $request->user();
        // LIKEのワイルドカード（% _）をキーワード内でも文字として扱う。
        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $key).'%';

        $books = Book::query()
            ->with('stock')
            ->whereRaw("title like ? escape '!'", [$pattern])
            ->when(
                $user?->isAdmin(),
                fn ($query) => $query->where('user_id', $user->id),
                fn ($query) => $query->where('is_for_sale', true),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->simplePaginate(9)
            ->withQueryString();

        return view('book.search', compact('books', 'key'));
    }

    /**
     * Google Books APIで本を検索する（管理者専用。ルート側のミドルウェアで制限）。
     *
     * @param  Request  $request  クエリ文字列 `q`（検索キーワード）を受け取るHTTPリクエスト
     * @return View 検索画面（キーワードが空の場合は入力フォームのみ）
     */
    public function searchGoogle(Request $request): View
    {
        $query = $request->string('q')->trim()->value();

        if ($query === '') {
            return view('book.search');
        }

        return $this->searchGoogleBooks($query);
    }

    /**
     * Google Books APIを検索し、結果を表示する。接続失敗・APIエラー時は分かりやすいメッセージを表示する。
     *
     * @param  string  $query  検索キーワード
     * @return View 検索結果、またはエラーメッセージを渡す画面
     */
    private function searchGoogleBooks(string $query): View
    {
        try {
            $response = Http::timeout(10)->get('https://www.googleapis.com/books/v1/volumes', array_filter([
                'q' => $query,
                'key' => config('services.google_books.key'),
            ]));
        } catch (ConnectionException $exception) {
            Log::warning('Google Books search failed to connect.', ['exception' => $exception->getMessage()]);

            return view('book.search', [
                'googleSearchError' => 'Google Booksへの接続に失敗しました。しばらくしてから再度お試しください。',
            ]);
        }

        if ($response->failed()) {
            Log::warning('Google Books search returned an error response.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return view('book.search', [
                'googleSearchError' => 'Google Books検索でエラーが発生しました。しばらくしてから再度お試しください。',
            ]);
        }

        // The view expects $json_decode (see resources/views/book/search.blade.php).
        return view('book.search', ['json_decode' => $response->json()]);
    }

    /**
     * 本の詳細を表示する。
     *
     * @param  Book  $book  ルートモデルバインディングで解決された対象の本
     * @return View 本の詳細画面
     */
    public function show(Book $book): View
    {
        Gate::authorize('view', $book);

        return view('book.show', compact('book'));
    }

    /**
     * 本の編集フォームを表示する。
     *
     * @param  Book  $book  ルートモデルバインディングで解決された対象の本
     * @return View 編集フォーム画面
     */
    public function edit(Book $book): View
    {
        Gate::authorize('update', $book);

        return view('book.edit', compact('book'));
    }

    /**
     * 本の情報を更新する。
     *
     * @param  UpdateBookRequest  $request  検証済みの更新内容（在庫数・販売可否を含む）
     * @param  Book  $book  ルートモデルバインディングで解決された対象の本
     * @return RedirectResponse 詳細画面へのリダイレクト
     */
    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        Gate::authorize('update', $book);

        $validated = $request->validated();
        $stock = $validated['stock'];
        unset($validated['stock']);
        $validated['is_for_sale'] = $request->boolean('is_for_sale');

        $book->update($validated);
        $book->stock()->updateOrCreate([], ['stock' => $stock]);

        return redirect()->route('book.show', ['book' => $book]);
    }

    /**
     * 本を削除する。所有者本人以外がアクセスした場合は403を返す。
     *
     * @param  Book  $book  ルートモデルバインディングで解決された対象の本
     * @return RedirectResponse 本棚一覧画面へのリダイレクト
     */
    public function destroy(Book $book): RedirectResponse
    {
        Gate::authorize('delete', $book);

        if ($book->image) {
            Storage::disk('bookimg')->delete('image/'.$book->image);
        }

        $book->delete();

        return redirect()->route('book.new');
    }
}
