# 実装ガイド：1機能を作るのに必要なクラス

このアプリで「1つの機能」を作るとき、どの層に・どのクラスを・どの順で作るかをまとめたドキュメントです。
実例は、すべてこのリポジトリに実在するコードから取っています（ファイルパスは各節に記載）。

- 対象読者: このアプリに機能を追加する開発者（自分自身を含む）
- 前提: Laravel 13 / PHP 8.3 以上 / Livewire + Volt（認証まわりのみ）/ Blade / SQLite

---

## 1. 全体像：リクエストが通る層

```
routes/web.php
   │  ミドルウェア（auth / role:admin / role:user）
   ▼
Controller ── FormRequest（入力の検証）
   │        ── Gate::authorize → Policy（「この行の持ち主か」）
   │        ── Service / Action（ロジックが複数の処理にまたがる場合）
   ▼
Model（Eloquent）── Migration / Factory
   ▼
View（Blade）── 必要に応じて Blade コンポーネント
```

| 層 | 置き場所 | 責務 |
| --- | --- | --- |
| ルート | `routes/web.php` | URL とミドルウェア、名前（`->name()`）の定義 |
| ミドルウェア | `app/Http/Middleware/EnsureUserHasRole.php` | 「管理者か個人ユーザーか」（ロール）の判定 |
| コントローラー | `app/Http/Controllers/` | リクエストを受け、結果を返す。薄く保つ |
| FormRequest | `app/Http/Requests/` | 入力値の検証ルール |
| Policy | `app/Policies/` | 「このデータの持ち主か」の判定（ロールは扱わない） |
| Service | `app/Services/` | 状態を持つ・複数箇所から使う処理（例: セッションカート） |
| Action | `app/Actions/` | 1つのユースケースを1つのトランザクションで完結させる処理 |
| モデル | `app/Models/` | テーブルとの対応、リレーション、アクセサ |
| Mailable | `app/Mail/` | メールの件名・本文・渡す変数 |
| ビュー | `resources/views/` | 画面。共通部品は `components/` |
| テスト | `tests/Feature/` | 機能単位の HTTP テスト |

### 役割分担の原則（ここを間違えない）

- **ロールの判定はルートのミドルウェア**、**所有者の判定は Policy**。Policy の中でロールは見ない（`BookPolicy`・`OrderPolicy` のコメント参照）。
- **ロジックが1画面で完結するならコントローラーに書く**。複数の処理を1つのトランザクションにまとめたくなったら **Action**、セッションなど状態を扱うなら **Service** に切り出す。
- 作成には `php artisan make:...` を使う（`--no-interaction` を付ける）。作成後は `vendor/bin/pint --dirty --format agent` で整形する。

---

## 2. 1機能を作るときのチェックリスト

新しい機能（例: 「お気に入り」）を追加するときは、次の順で進めます。

| # | 作るもの | コマンド | 必須か |
| --- | --- | --- | --- |
| 1 | マイグレーション | `php artisan make:migration create_xxx_table` | テーブルが増えるなら必須 |
| 2 | モデル + ファクトリ | `php artisan make:model Xxx -f`（`-mf` でマイグレーションも同時に作成） | 必須 |
| 3 | FormRequest | `php artisan make:request StoreXxxRequest` | 入力があるなら必須 |
| 4 | Policy | `php artisan make:policy XxxPolicy --model=Xxx` | 「持ち主だけ操作可」なら必須 |
| 5 | Service / Action | `php artisan make:class Services/XxxService` | 必要な場合のみ |
| 6 | コントローラー | `php artisan make:controller XxxController` | 必須 |
| 7 | ルート | `routes/web.php` を編集 | 必須 |
| 8 | ビュー | `resources/views/xxx/*.blade.php` | 必須 |
| 9 | Mailable（メールを送るなら） | `php artisan make:mail XxxMail --markdown=mail.xxx` | 必要な場合のみ |
| 10 | テスト | `php artisan make:test XxxControllerTest --phpunit` | 必須（変更ごとに追加・更新） |

補足:

- モデルを作ったら**ファクトリも必ず作る**（`database/factories/`）。ロール付きユーザーは `User::factory()->admin()` のように状態（state）で作る。
- ルートを追加したら、`.ai/rules/routes.md` を先に読む（`/shop/{shop}` のワイルドカードは `auth` グループより後ろに置く、など）。
- 新しい設定値は `.env` → `config/*.php` 経由で読む（コードから `env()` を直接呼ばない。例: `config('services.google_books.key')`）。

---

## 3. パターン別ガイド

### 3-1. CRUD（本の登録・詳細・編集・削除）

実例: `BookController`（[app/Http/Controllers/BookController.php](../app/Http/Controllers/BookController.php)）

**必要なクラス**

| クラス | ファイル | 役割 |
| --- | --- | --- |
| Controller | `app/Http/Controllers/BookController.php` | `create` / `show` / `edit` / `update` / `destroy` |
| FormRequest（登録） | `app/Http/Requests/StoreBookRequest.php` | 登録時の検証。ジャンル一覧 `TYPES` もここが持つ |
| FormRequest（更新） | `app/Http/Requests/UpdateBookRequest.php` | 更新時の検証 |
| Policy | `app/Policies/BookPolicy.php` | `view` / `update` / `delete` を「所有者のみ」で判定 |
| Model | `app/Models/Book.php`、`app/Models/Stock.php` | `Book hasOne Stock`、`Book belongsTo User` |
| Migration | `create_books_table`、`add_columns_to_books_table`、`create_stocks_table` ほか | テーブル定義 |
| Factory | `database/factories/BookFactory.php`、`StockFactory.php` | テスト用データ |
| View | `resources/views/book/{create,edit,show,new,search}.blade.php` | 画面 |
| Blade コンポーネント | `resources/views/components/book-card.blade.php` | 本のカード（一覧で共用） |
| Test | `tests/Feature/BookControllerTest.php` | 検証・認可・保存内容 |

**ルート**（`routes/web.php`、管理者専用グループ）

```php
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::match(['get', 'post'], '/book/create', [BookController::class, 'create'])->name('book.create');
    Route::get('/book/{book}', [BookController::class, 'show'])->name('book.show');
    Route::get('/book/{book}/edit', [BookController::class, 'edit'])->name('book.edit');
    Route::put('/book/{book}/update', [BookController::class, 'update'])->name('book.update');
    Route::delete('/book/{book}/delete', [BookController::class, 'destroy'])->name('book.destroy');
});
```

**実装の流れ**

1. **登録（Create）**: フォーム表示（GET）と登録（POST）を同じルートで受けている。
   - 検証は FormRequest を型ヒントせず、POST の分岐内で `$request->validate((new StoreBookRequest)->rules())` を呼ぶ。GET でも FormRequest が動いて、フォームが表示されずリダイレクトされるのを避けるため（`.ai/rules/controllers.md`）。
   - 新しく GET/POST 兼用のアクションを作る場合は、URL を分けて FormRequest を型ヒントするのが素直。
   - 本と在庫は別テーブルなので、`Book::create()` のあとに `Stock::create()` する。
2. **詳細・編集・削除**: 冒頭で `Gate::authorize('view'|'update'|'delete', $book)`。他人の本は 403 になる。`abort_unless($book->user_id === Auth::id(), 403)` のような手書きの判定は書かない。
3. **更新**: `UpdateBookRequest` を型ヒントし、`$request->validated()` から在庫数を取り出して分ける。`$book->stock()->updateOrCreate([], ['stock' => $stock])` で、在庫行がなくても作られる。チェックボックス（`is_for_sale`）は未送信だと欠けるので `$request->boolean('is_for_sale')` で受ける。
4. **削除**: 画像ファイルを消してからレコードを削除する（次節）。
5. **画面の出し分け**: 編集・削除ボタンの表示判定は `book-card` コンポーネントにあるが、これは表示上の便宜であって認可ではない。**認可は必ずコントローラーの `Gate::authorize()`**。

**テストに書くこと**（`BookControllerTest`）

- ゲスト → ログインへリダイレクト、個人ユーザー → 403
- 必須項目・ジャンル外の値が弾かれる（検証）
- 正常系で DB に保存され、想定のURLへリダイレクトされる
- 他の管理者の本を見る・編集する → 403（Policy）

---

### 3-2. ファイルアップロード（本の表紙画像）

実例: `BookController::create` / `destroy`、`UserController::update`、`Book::imageUrl()`

**必要なもの**

| もの | 場所 | 役割 |
| --- | --- | --- |
| 専用ディスク `bookimg` | `config/filesystems.php` | 保存先。`root` は `public_path()` |
| 検証ルール | `StoreBookRequest::rules()` の `'image' => ['nullable', 'image', 'max:10240']` | 画像であること、10MB 以下 |
| フォーム | `resources/views/book/create.blade.php` | `enctype="multipart/form-data"` と `<input type="file" name="image">` |
| 保存処理 | コントローラー | `storeAs('image', $name, 'bookimg')` |
| 表示用アクセサ | `app/Models/Book.php` の `imageUrl()` | ファイル名 or 外部URL を表示用URLに変換 |
| 削除処理 | `BookController::destroy` | `Storage::disk('bookimg')->delete('image/'.$book->image)` |
| テスト | `Storage::fake('bookimg')` → `Storage::disk('bookimg')->assertExists(...)` | 実ファイルを作らずに検証 |

**保存の流れ**（`BookController::create`）

```php
if ($request->hasFile('image')) {
    $imageName = $request->file('image')->getClientOriginalName();
    $request->file('image')->storeAs('image', $imageName, 'bookimg');
} elseif ($request->filled('google_image_url')) {
    $imageName = $validated['google_image_url'];   // Google Books のサムネイルURL
}
```

- DB の `books.image` には**ファイル名**（または外部URL）だけを保存する。
- 画像の優先順位は「アップロードされたファイル > Google Books のURL」（テスト: `test_uploaded_image_takes_precedence_over_the_google_thumbnail_url`）。
- 表示は `Book::imageUrl()` が判定する。`http(s)://` で始まる値はそのまま、それ以外は `asset('image/'.$name)` に解決する。ビューでは `$book->image_url` を使う。

**注意点**

- **`bookimg` ディスクを使う理由**: 元の Laravel 6 アプリの慣習を踏襲しており、`asset('image/...')` で直接配信するため `storage:link` を使わない。`public` ディスクへ変更する場合は、表示側（`book-card.blade.php` と `imageUrl()`）も一緒に直す（`.ai/rules/models.md`）。
- **ファイル名は元のファイル名のまま**（`getClientOriginalName()`）。同じ名前のファイルをアップロードすると上書きされ、別の本の画像も変わる。また、削除時も同名の画像を使う他の本があれば表示が壊れる。ファイル名の衝突を避けたい場合は `store('image', 'bookimg')` でハッシュ名にするなど、別途対応が必要（現状の仕様としては未対応）。
- ショップ画像（`UserController::update`）も同じ `bookimg` ディスクを使っている。

---

### 3-3. メール送信（Queue）

実例: 発送メール（`SellerOrderController::ship` → `OrderShippedMail`）

**必要なクラス・設定**

| もの | 場所 | 役割 |
| --- | --- | --- |
| Mailable | `app/Mail/OrderShippedMail.php` | 件名（`envelope()`）と本文・変数（`content()`） |
| メールテンプレート | `resources/views/mail/order-shipped.blade.php` | Markdown メール（`<x-mail::message>`） |
| 送信箇所 | `SellerOrderController::ship()` | `Mail::to($order->user)->queue(new OrderShippedMail($order, $items))` |
| Policy | `app/Policies/OrderPolicy.php` の `ship` | 自分の本を含む注文だけ操作可 |
| Queue 用テーブル | `0001_01_01_000002_create_jobs_table.php` | `QUEUE_CONNECTION=database` のジョブ保管先 |
| 環境変数 | `.env` の `QUEUE_CONNECTION`、`MAIL_MAILER` | 既定は `database` と `log` |
| ワーカー | `php artisan queue:work` | ジョブを取り出して送信する |
| テスト | `Mail::fake()` → `Mail::assertQueued(...)` | 実際に送らず、キューに積まれたかを検証 |

**実装の流れ**

1. `php artisan make:mail OrderShippedMail --markdown=mail.order-shipped` で Mailable とテンプレートを作る。
2. Mailable は `Queueable` と `SerializesModels` を使う。渡した Eloquent モデルは **ID だけがシリアライズ**され、ワーカー実行時に DB から再取得される。したがって、コンストラクタで渡す時点の値ではなく、**実行時点の DB の値**が使われる。
3. コントローラーで `Mail::to(...)->queue(...)`。`send()` ではなく `queue()` を使うと、HTTP レスポンスを待たせずにワーカーが送る。
4. 開発時は `MAIL_MAILER=log`。メールは送られず、`storage/logs/laravel.log` に内容が出る。
5. **コードを変更したらワーカーを再起動する**（ワーカーは起動時のコードを保持し続けるため）。

**二重送信の防ぎ方**（`SellerOrderController::ship`）

```php
$shipped = $order->items()
    ->where('seller_id', Auth::id())
    ->whereNull('shipped_at')
    ->update(['shipped_at' => now()]);

if ($shipped === 0) {
    return back()->with('status', __('この注文はすでに発送済みです。'));
}
// ↑ 更新できた行があった場合だけ、メールをキューに積む
```

- 二重クリックや同時リクエストでも、`shipped_at IS NULL` の行を更新できた側だけがメールを積む。
- 発送の単位は「注文 × 出品者」。同じ出品者の本は1通にまとまり、別の出品者の本は別のメールになる。
- 状態は `OrderItem.shipped_at`（`Order` ではない）で管理している。

**テストに書くこと**（`SellerOrderControllerTest`）

- 発送するとメールがキューに積まれる（`Mail::assertQueued(OrderShippedMail::class, ...)`）
- 二度目の発送ではメールが積まれない
- 出品者が2人いる注文では、メールが出品者ごとに1通ずつ（合計2通）になる
- 他の出品者の注文・本を含まない注文は 403

---

### 3-4. 注文の確定（Action + トランザクション）

実例: `CheckoutController::store` → `PlaceOrderAction`

在庫確認・注文作成・在庫更新は、**途中で失敗したら何も残さない**必要があります。こうした処理は Action に切り出します。

| もの | 場所 | 役割 |
| --- | --- | --- |
| Controller | `app/Http/Controllers/CheckoutController.php` | カートが空ならカートへ戻し、Action を呼び、成功したらカートを空にする |
| Action | `app/Actions/PlaceOrderAction.php` | `handle()` の中で `DB::transaction()` |
| Model | `Order`、`OrderItem`、`Stock`、`Book` | 注文と注文商品の保存、在庫の減算 |
| Migration | `create_orders_table`、`create_order_items_table`、`add_seller_id_to_order_items_table`、`add_shipped_at_to_order_items_table` | テーブル定義（後からの列追加は別マイグレーション） |
| Test | `CheckoutControllerTest`、`SellerOrderControllerTest` | アクセス制御と、注文商品に出品者IDが記録されること（在庫不足時のロールバックを直接検証するテストは、現状ない） |

要点:

- Action は `handle()` を持つ普通のクラス。**コントローラーのメソッド引数に型ヒントするだけ**でコンテナが注入する（`store(PlaceOrderAction $placeOrder)`）。
- 売り越し防止のため、在庫行は `lockForUpdate()` でロックしてから確認・更新する。
- `OrderItem` には、購入時点の**タイトル・価格・出品者（`seller_id`）を複製して保存する**。あとで本が削除・値上げされても注文履歴が変わらない（`book_id` は `nullOnDelete`）。
- 失敗は `ValidationException` で伝え、画面にはエラーメッセージとして戻す。

---

### 3-5. セッションを使う機能（カート）

実例: `CartController` + `CartService` + `AddCartItemRequest` / `UpdateCartItemRequest`

| もの | 場所 | 役割 |
| --- | --- | --- |
| Service | `app/Services/CartService.php` | セッションの `cart`（`book_id => 数量`）の読み書きを一元化 |
| Controller | `app/Http/Controllers/CartController.php` | 表示・追加・数量変更・削除。`CartService` をコンストラクタで受ける |
| FormRequest | `AddCartItemRequest`、`UpdateCartItemRequest` | 数量や `book_id` の検証 |
| ルート | `Route::middleware('role:user')` のグループ | ゲストと個人ユーザーが使える。管理者は 403 |
| Test | `CartControllerTest` | ゲスト・個人ユーザー・管理者ごとの利用可否（在庫超過などの検証は、現状テストが少ない） |

要点:

- DB にカートのテーブルは持たない。**セッションには `book_id` と数量だけ**を入れ、タイトルや価格は表示のたびに DB から取る（古い価格が残らない）。
- 在庫の確認（`assertWithinStock`）は Service の中に閉じ込める。
- `AddCartItemRequest` は `Rule::exists('books', 'id')->where(...)` で、販売対象の本だけを受け付ける。
- Service は `Session` をコンストラクタで受けるだけなので、コンテナが自動で組み立てる（ServiceProvider への登録は不要）。

---

### 3-6. 外部 API 連携（Google Books 検索）

実例: `BookController::searchGoogle` / `searchGoogleBooks`（管理者専用。`GET /book/google-search?q=`、ルート名 `book.searchGoogle`）

- サイト内のタイトル検索（`BookController::search`、`GET /search?q=`）は誰でも使える公開ルート。Google 検索とは別のアクション・別のルートに分けてある。
- `Http::timeout(10)->get(...)` で呼び出す。API キーは `config('services.google_books.key')`（`.env` の `GOOGLE_BOOKS_API_KEY`、任意）。
- **失敗時の処理を必ず書く**: `ConnectionException` の catch と `$response->failed()` の両方で、`Log::warning()` を出して画面に分かりやすいメッセージを返す。
- テストでは `Http::fake()` で API 応答を差し替える。成功・APIエラー・接続失敗の3ケースと、キー送信の有無を検証している（`BookControllerTest`）。
- 検索結果のビュー変数名は `$json_decode`（`book/search.blade.php` が読む名前と一致させる）。

---

### 3-7. 認可（ロールと所有者）

| 判定したいこと | 使うもの | 実例 |
| --- | --- | --- |
| 管理者か個人ユーザーか | ルートのミドルウェア `role:admin` / `role:user` | `EnsureUserHasRole`、`UserRole` enum |
| ログイン済みか | ルートのミドルウェア `auth` | |
| この行の持ち主か | Policy + `Gate::authorize()` | `BookPolicy`、`OrderPolicy` |

- `EnsureUserHasRole` は**ゲストを素通り**させる。ゲストを弾くのは `auth` の役割なので、ログイン必須の画面は `['auth', 'role:xxx']` の2つを並べる。
- ルートのグループ分け（`routes/web.php`）:

| グループ | ミドルウェア | 使える人 |
| --- | --- | --- |
| `/new`（入荷本一覧）、`/search`（タイトル検索） | なし | 全員（`/search` の管理者は自分の店の本だけが対象） |
| `/store`、`/cart` | `role:user` | ゲスト + 個人ユーザー |
| `/book/*`（Google検索 `/book/google-search` を含む）、`/shop/edit`、`/shop/orders` | `auth` + `role:admin` | 管理者 |
| `/checkout`、`/orders` | `auth` + `role:user` | 個人ユーザー |

---

## 4. テストの書き方

- 機能の変更には**必ずテストを追加・更新**する。まず `tests/Feature/` に、機能ごとのファイル（`XxxControllerTest`）を作る。
- テストデータは**必ずファクトリで作る**。`User::factory()->admin()->create()` のように状態を使う。
- クラスには `RefreshDatabase` を使う。
- 外部への副作用は fake にする。

| 副作用 | 使うもの |
| --- | --- |
| メール | `Mail::fake()` + `Mail::assertQueued()` |
| ファイル保存 | `Storage::fake('bookimg')` + `assertExists()` |
| 外部 API | `Http::fake()` |

- 検証は3種類を最低限そろえる: ①**アクセス制御**（ゲスト・別ロール・他人）、②**入力検証**、③**正常系の保存結果**。
- 画面変数の検証は `assertViewHas` だけに頼らず、`assertSee` で**描画結果**を確認する（変数名の食い違いを検出できる）。
- 実行:

```
php artisan test --compact tests/Feature/BookControllerTest.php
php artisan test --compact --filter=test_valid_submission_creates_a_book_with_stock_and_redirects
```

---

## 5. 追加前に必ず確認すること

- `.ai/rules/index.md` を開き、変更するファイルに該当するルールファイルを読む（`.ai/rules/*.md`）。
- 既存の兄弟ファイルの書き方（命名・PHPDoc・型宣言・波括弧）に合わせる。PHPDoc は日本語で、引数と戻り値の説明を書く。
- 新しい依存パッケージは、承認なしに追加しない。
- 変更後は `vendor/bin/pint --dirty --format agent` を実行する。
- 画面の変更が反映されない場合は、`npm run build`（または `npm run dev` / `composer run dev`）が必要。

---

## 付録：ファイル一覧（機能別）

| 機能 | Controller | Request | Policy | Service / Action | Model | Mail | 主なテスト |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 本の管理・検索 | `BookController` | `StoreBookRequest`、`UpdateBookRequest` | `BookPolicy` | — | `Book`、`Stock` | — | `BookControllerTest`、`Unit/Models/BookTest` |
| 本屋 | `StoreController` | — | — | — | `Book` | — | `StoreControllerTest` |
| カート | `CartController` | `AddCartItemRequest`、`UpdateCartItemRequest` | — | `CartService` | `Book` | — | `CartControllerTest` |
| 注文（購入者） | `CheckoutController`、`OrderController` | — | — | `PlaceOrderAction`、`CartService` | `Order`、`OrderItem`、`Stock` | — | `CheckoutControllerTest`、`OrderControllerTest` |
| 受注・発送（出品者） | `SellerOrderController` | — | `OrderPolicy` | — | `Order`、`OrderItem` | `OrderShippedMail` | `SellerOrderControllerTest` |
| ショップ情報 | `UserController` | `UpdateShopRequest` | — | — | `User` | — | `UserControllerTest` |
| 認証・プロフィール | `Auth/VerifyEmailController`、Volt（`resources/views/livewire/`） | `LoginForm` | — | — | `User` | — | `tests/Feature/Auth/*`、`ProfileTest` |
