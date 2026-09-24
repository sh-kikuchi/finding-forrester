# finding-forrester (re:vue mix)

このリポジトリは、[HMisawa3/finding-forrester](https://github.com/HMisawa3/finding-forrester) をフォークして作成したものです。

## アプリ概要

個人の本屋（店舗）が本を出品・管理し、購入者が本を探して注文できる、本屋向けのWebアプリです。ユーザーには2つの役割（ロール）があります。

| ロール | できること |
| --- | --- |
| 管理者（店舗） | 本の登録・編集・削除、在庫と販売可否の管理、ショップ情報の編集、受注の確認、注文の発送（購入者へ発送メールを送信） |
| 個人ユーザー（購入者） | 本屋での本の閲覧、カートへの追加、注文の確定、注文履歴の確認 |

ゲスト（未ログイン）でも、入荷本一覧・本屋・カートは利用できます。注文の確定にはログインが必要です。

### 主な機能

- **入荷本一覧（`/new`）**: 直近1ヶ月に登録された本を、誰でも閲覧できます。
- **本の検索・登録（管理者）**: サイト内の本をタイトルで検索できます。Google Books API の検索結果から、本の情報を登録フォームへ引き継ぐこともできます。
- **本屋（`/store`）**: 販売中の本をジャンルで絞り込んで閲覧し、カートへ追加できます。
- **カート・注文**: カートはセッションで保持します。注文確定時に在庫を確認し、注文と在庫の更新を1つのトランザクションで行います。
- **受注管理（管理者・`/shop/orders`）**: 自分の本を含む注文を、注文単位で確認できます。「発送する」を押すと、その注文のうち自分の本だけが発送済みになります。
- **発送メール**: 発送すると、購入者へ発送メールをキュー経由で送信します。発送の単位は「注文 × 出品者」です。同じ出品者の本は1通にまとまり、同じ注文に別の出品者の本があれば、出品者ごとに別のメールになります。

### 技術スタック

- PHP 8.3 以上 / Laravel 13
- Livewire 3 / Volt（認証まわりの画面）
- Tailwind CSS / Vite
- DB: SQLite（既定）
- キュー・セッション・キャッシュ: データベースドライバ（既定）

## 準備

### 必要なもの

- PHP 8.3 以上
- Composer
- Node.js / npm

### セットアップ

```
composer install
cp .env.example .env
php artisan key:generate
```

SQLite を使う場合は、DBファイルを作成してからマイグレーションを実行します。

```
touch database/database.sqlite
php artisan migrate
npm install
npm run build
```

`composer run setup` で、上の手順（`.env` の作成・キー生成・マイグレーション・フロントエンドのビルド）をまとめて実行することもできます。

### 動作確認用のデータ

シーダーを実行すると、動作確認用のユーザーが2人作成されます。パスワードはどちらも `password` です。

```
php artisan db:seed
```

| 役割 | メールアドレス |
| --- | --- |
| 個人ユーザー | test@example.com |
| 管理者（店舗） | shop@example.com |

### 環境変数（`.env`）

| 変数 | 説明 |
| --- | --- |
| `QUEUE_CONNECTION` | 既定は `database`。発送メールはキューに積まれるため、実行にはワーカーの起動が必要です（下記「実行」を参照）。 |
| `MAIL_MAILER` | 既定は `log`。メールは実際には送信されず、`storage/logs/laravel.log` に内容が出力されます。実際に送る場合は `smtp` などに変更し、`MAIL_HOST` などの接続情報も設定します。 |
| `GOOGLE_BOOKS_API_KEY` | 任意。未設定でも Google Books 検索は動きますが、匿名の共有クォータは小さく、制限（429）に当たりやすくなります。 |

### テスト

```
php artisan test
```

## 実行

キューワーカーを起動します。発送メールはキューに積まれるため、ワーカーが動いていないとメールは処理されません。コードを変更した場合は、ワーカーを再起動してください。

```
php artisan queue:work
```

開発サーバーを起動します。

```
php artisan serve
```
or
```
composer run dev
```
※`npm run dev`も併せて実行したい場合に便利。

### 発送メールの確認方法（開発時）

`MAIL_MAILER=log` の場合、メールは送信されず、`storage/logs/laravel.log` に宛先・件名・本文が出力されます。

1. `php artisan queue:work` を起動しておく
2. 管理者（`shop@example.com`）でログインし、受注一覧（`/shop/orders`）で「発送する」を押す
3. `storage/logs/laravel.log` の末尾で、メールの内容を確認する

## Special Thanks

このアプリは、[HMisawa3](https://github.com/HMisawa3) さんが作成された [finding-forrester](https://github.com/HMisawa3/finding-forrester) をもとにしています。リミックスを快く承諾してくださったことに、心から感謝いたします。
