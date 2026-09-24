---
paths:
  - 'app/Http/Controllers/BookController.php,app/Http/Requests/*Book*,app/Models/Book.php,app/Models/Stock.php,config/filesystems.php'
---

# Models

## Book cover images use the 'bookimg' disk, not 'public'/storage:link
Book cover uploads are stored via a dedicated `bookimg` filesystem disk (config/filesystems.php) whose root is `public_path()`, saved with `$request->file('image')->storeAs('image', $name, 'bookimg')`. This mirrors the legacy Laravel 6 app's convention and is required because `resources/views/components/book-card.blade.php` reads the image with `asset('image/'.$book->image)` — a direct public path, not the `/storage` symlink. Don't switch Book image uploads to the `public` disk without also updating that view.

Book/Stock schema: `books` table gained `user_id, title, author, type, image` via a follow-up migration (the original `create_books_table` migration only has `id`+timestamps); `stocks` table (`book_id`, `stock`) was added new — the legacy app's unused `Stock.difference` column was intentionally dropped per `laravel13-migration-audit.md`. Book hasOne Stock, belongsTo User.

Allowed `type` genre values live in `App\Http\Requests\StoreBookRequest::TYPES` and are duplicated in `book/create.blade.php` and `book/edit.blade.php` (`$types` array) — keep them in sync if the genre list changes.
