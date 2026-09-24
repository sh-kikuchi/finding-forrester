# Laravel 6 → Laravel 13 移行 事前調査ドキュメント

- 調査日: 2026-09-05
- 対象: 現行 Laravel 6 アプリケーション(書籍売買サイト想定: 出品者/購入者=User、出品書籍=Book、在庫=Stock、メッセージ=Mail)
- 目的: Laravel 13 への書き換えにあたり、実装すべき範囲(実際に画面から使われている機能)と、切り捨ててよい範囲(未実装 or 画面から参照のない機能)を切り分ける
- 調査方法: `routes/web.php` / `app/Http/Controllers` / `app/*.php`(Model) / `resources/views` を読み取り専用で調査(コード変更なし)

---

## 1. 結論サマリ

| 区分 | 件数 | 対応方針 |
|---|---|---|
| 実装済み・画面からも利用中 | 15ルート | Laravel 13へ移植対象 |
| ルート/コントローラーはあるが画面から未使用 | 1ルート(`book.soldOuts`) | 移植不要(要件確認の上ドロップ候補) |
| コントローラー未実装 かつ 画面からも未使用 | 6ルート(`book.store`, `mail.*` 5件) | **移植不要。実装しない** |
| デッドファイル(ビュー) | `welcome.blade.php` | 移植不要 |

→ 実質的に移植が必要なのは **BookController(6アクション) + UserController(3アクション) + 認証系(標準)** のみで、`MailController`(メール/メッセージ機能)は画面側の導線が一切なく仕様として存在しないため、Laravel 13側でも実装しない方針で問題ないと判断。

---

## 2. ルーティング一覧(実装状況・画面利用有無つき)

凡例: 実装 = コントローラーにメソッドが存在するか / 画面利用 = view からのフォームaction・リンクhrefで参照されているか

### 2.1 認証不要

| Method | URI | Route名 | Controller@Action | 実装 | 画面利用 | 備考 |
|---|---|---|---|---|---|---|
| GET | `/` | `home` | `BookController@index` | ○ | ○ | トップページ表示のみ |
| POST | `/` | `home`(同名重複) | `BookController@index` | ○ | – | GETと同名登録。**Laravel13では名前重複を解消すべき** |
| GET | `/book` | `book.home` | `BookController@home` | ○ | ○ | ログインユーザーの本一覧(header内リンク) |
| GET | `/login` | `login` | `Auth\LoginController@showLoginForm` | ○ | ○ | |
| POST | `/login` | (名前なし) | `Auth\LoginController@login` | ○ | ○ | `auth/login.blade.php` フォーム |
| GET | `/register` | `register` | `Auth\RegisterController@showRegistrationForm` | ○ | ○ | |
| POST | `/register` | (名前なし) | `Auth\RegisterController@register` | ○ | ○ | `auth/register.blade.php` フォーム |
| GET | `/logout` | `logout` | `Auth\LoginController@logout` | ○ | ○ | header内リンク |
| GET | `/new` | `book.new` | `BookController@new` | ○ | ○ | header内リンク(新着書籍一覧) |
| GET | `/book/sold-out` | `book.soldOuts` | `BookController@soldOuts` | **×未実装** | **×未使用** | ソース内コメント「なんのために必要？」あり。**実装・移植不要** |
| GET/POST | `/book/search` | `search` | `BookController@search` | ○ | ○ | header内リンク＋`book/search.blade.php`フォーム2種 |

### 2.2 `middleware('auth')`

| Method | URI | Route名 | Controller@Action | 実装 | 画面利用 | 備考 |
|---|---|---|---|---|---|---|
| GET | `/book/create` | `book.create` | `BookController@create` | ○ | ○ | header内リンク |
| POST | `/book/create` | (名前なし) | `BookController@create` | ○ | ○ | `book/create.blade.php` フォーム(実質の登録処理はこちら) |
| POST | `/book/store` | `book.store` | `BookController@store` | **×未実装** | **×未使用** | 登録フォームは`/book/create`宛のみ。`store`は誰からも呼ばれないデッドルート。**実装・移植不要** |
| GET | `/book/{book}` | `book.show` | `BookController@show` | ○ | △一部 | `book/edit.blade.php`からのリンクのみ。一覧(`book/index`,`book/new`)からの直接導線はない(Laravel13で導線追加を検討してもよい) |
| GET | `/book/{book}/edit` | `book.edit` | `BookController@edit` | ○ | ○ | `book/show.blade.php`からのリンク |
| PUT | `/book/{book}/update` | `book.update` | `BookController@update` | ○ | ○ | `book/edit.blade.php` フォーム(`@method('PUT')`) |
| GET | `/shop/edit` | `shop.edit` | `UserController@edit` | ○ | ○ | header内リンク |
| PUT | `/shop/update` | `shop.update` | `UserController@update` | ○ | ○ | `user/edit.blade.php` フォーム(`@method('PUT')`) |
| GET | `/mail/create` | `mail.create` | `MailController@create` | **×未実装** | **×未使用** | 画面側に`mail`関連の文言・リンクが一切なし |
| POST | `/mail/store` | `mail.store` | `MailController@store` | **×未実装** | **×未使用** | 同上 |
| GET | `/mail/index` | `mail.index` | `MailController@index` | **×未実装** | **×未使用** | 同上 |
| POST | `/mail/update/{mail}` | `mail.update` | `MailController@update` | **×未実装** | **×未使用** | 同上 |
| GET | `/mail/return` | `mail.returns` | `MailController@returns` | **×未実装** | **×未使用** | 同上 |

### 2.3 グループ外

| Method | URI | Route名 | Controller@Action | 実装 | 画面利用 | 備考 |
|---|---|---|---|---|---|---|
| GET | `/shop/{shop}` | `shop.show` | `UserController@show` | ○ | ○ | `book/search.blade.php`検索結果の「取扱店舗」リンク |

### 2.4 その他

- `routes/api.php`: デフォルトの `GET /user`(`auth:api`)のみ。実利用なし。Laravel13移行でAPIを新設しない限り不要。
- `routes/channels.php`, `routes/console.php`: 標準内容、対応不要。

---

## 3. 画面(View)からの通信一覧

### 3.1 View構成

```
resources/views/
├── auth/            login.blade.php, register.blade.php
├── book/            create, edit, home, index, new, search, show (計7)
├── layouts/          app.blade.php(共通レイアウト), header.blade.php(共通ヘッダー・ナビ)
├── user/            edit.blade.php, shop.blade.php
└── welcome.blade.php  ← どこからも@extends/view()されないデッドファイル
```

全13ビュー(レイアウト2含む)。JavaScriptからのfetch/axios/$.ajax通信は**プロジェクト全体で0件**(`bootstrap.js`でaxiosをセットアップしているのみで未使用)。通信は全てHTMLフォームのsubmitと通常のリンク遷移のみ。

### 3.2 フォーム一覧(種類・入力項目)

| # | 画面 | 送信先(Method) | 入力フィールド | 用途 |
|---|---|---|---|---|
| 1 | auth/login | POST `/login` | email, password | ログイン |
| 2 | auth/register | POST `/register` | name, email, password, password_confirmation, address, tel, time | 会員登録 |
| 3 | book/create | POST `/book/create` (multipart) | title, author, type(select), stock, image(file) | 書籍出品登録 |
| 4 | book/edit | PUT `/book/{book}/update` | title, author, type(select) | 書籍情報編集 |
| 5 | book/search (フォーム1) | POST `/book/search` | a_search | サイト内タイトル検索 |
| 6 | book/search (フォーム2) | POST `/book/search` | b_search | Google Books API検索 |
| 7 | user/edit | PUT `/shop/update` (multipart) | name, email, password(readonly), newPassword, address, tel, time, image(file) | 店舗(自分)情報編集 |

→ **フォーム総数: 7種類、送信先ルート実質6本**(search系2フォームは同一ルート)。

### 3.3 リンク遷移一覧(主要導線)

| 画面 | リンク先 | 経由 |
|---|---|---|
| layouts/header(共通) | home, book.home, search, book.new, book.create, shop.edit, logout | ヘッダーナビ(認証状態で出し分け) |
| auth/login | register | 「新規登録はこちら」導線 |
| book/edit | book.show | 「詳細画面へ」 |
| book/search | shop.show | 検索結果の「取扱店舗」 |
| book/show | book.edit | 「編集」 |

### 3.4 画面遷移フロー(概略)

```
[/ (home)] ── login/register ──> [/book (book.home)]
                                        │
                       ┌────────────────┼────────────────┐
                       ▼                ▼                ▼
                 [/new 新着]      [/book/create 出品]   [/book/search 検索]
                                        │                    │
                                        ▼                    ▼
                                 (create送信→一覧へ)   [/shop/{shop} 店舗詳細]

[/book/{book}/edit] ←── [/book/{book} show] ※一覧からの直接導線なし、edit経由のみ
        │
        ▼ (PUT update)
   一覧へ戻る

[/shop/edit] (PUT shop.update) 自分の店舗情報編集
```

---

## 4. コントローラー一覧と役割

| コントローラー | Middleware | 実装アクション | 使用Model | 備考 |
|---|---|---|---|---|
| `Controller`(基底) | – | – | – | 未使用の`execute_api()`ヘルパーあり(`dd()`残存、デバッグ未完了)。**移植不要** |
| `BookController` | なし(ルート側でauth制御) | index, home, new, create, search, show, edit, update | Book, Stock | `create`で画像アップロード＋`Stock`同時作成。`search`はサイト内検索＋Google Books API外部検索の2系統 |
| `UserController` | なし(ルート側でauth制御) | show, edit, update | User | 店舗情報表示・自己プロフィール編集(パスワード変更含む) |
| `MailController` | – | **なし(空クラス)** | – | **移植不要**(画面導線なし) |
| `Auth\LoginController` | `guest`(logout除く) | showLoginForm, login, logout | User | 標準スキャフォールディングをカスタマイズ(ログイン成功後`/book`へ) |
| `Auth\RegisterController` | `guest` | showRegistrationForm, register | User | validator/createをカスタマイズ(address, tel, time追加) |
| `Auth\ForgotPasswordController` 等 | 標準 | 標準のまま | User | 実際に使われているか要確認(view側にパスワード再設定への導線は未検出) |

---

## 5. モデル関係(ER)

```
User (1) ── (N) Book      User hasMany Book / Book belongsTo User
User (1) ── (N) Mail      User hasMany Mail / Mail belongsTo User  ※Mail機能自体は未実装
Book (1) ── (1) Stock     Book hasOne Stock / Stock belongsTo Book
```

- `User`: name, email, password, address, tel, time, image
- `Book`: user_id, title, author, type, image
- `Stock`: book_id, stock, difference(※`difference`カラムは現状どこからも更新されていない未使用フィールド)
- `Mail`: fillable未指定(機能自体が未実装のため詳細不要)

---

## 6. Laravel 13移行時の実装範囲(推奨)

### 移植する
- 認証(login/register/logout) ※Laravel13標準のBreeze/Fortify等への置き換えを推奨
- 書籍出品(create)・一覧(home/new)・検索(search、サイト内＋Google Books API)・詳細(show)・編集(edit/update)
- 店舗情報表示(shop.show)・自己プロフィール編集(shop.edit/update)
- Book⇔Stock(在庫)の1:1関係、出品時の在庫同時作成

### 移植しない(未実装 かつ 画面からの参照なし)
- `book.soldOuts`(`/book/sold-out`)
- `book.store`(`/book/store`)
- `MailController`一式(`mail.create/store/index/update/returns`)とそれに伴う`Mail`モデルのメッセージ機能
- `welcome.blade.php`(デッドファイル)
- ルート名`home`のPOST重複登録(整理してGETのみに)

### 移行時に要件確認したい点(申し送り)
1. `Stock.difference`カラムは現行コードで一切更新されておらず、在庫調整機能(入荷/出荷差分)が未実装のまま。Laravel13で在庫管理を強化するなら要件を新規に詰める必要あり。
2. `book.show`への導線が一覧画面(`book/index`, `book/new`)に存在しない(編集画面経由のみ)。UI改善の余地あり。
3. `ForgotPasswordController`等パスワード再設定系は標準のまま実装されているが、view側に導線が見当たらないため実際に使われているか要確認。
4. Google Books API検索(`file_get_contents`で直接HTTP通信)は、Laravel13移行時に`Http`ファサード(Guzzle経由)へ置き換えるのが望ましい。
