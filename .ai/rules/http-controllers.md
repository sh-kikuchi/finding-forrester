---
paths:
  - 'app/Policies/BookPolicy.php,app/Http/Controllers/BookController.php'
---

# Http Controllers

## BookPolicy is wired up — it owns ownership checks, not role checks
`App\Policies\BookPolicy` is real: `view`/`update`/`delete` each check `$user->id === $book->user_id`, and `BookController` calls `Gate::authorize('view'|'update'|'delete', $book)` in `show()`/`edit()`/`update()`/`destroy()`. Don't reintroduce a manual `abort_unless($book->user_id === Auth::id(), ...)` check — use `Gate::authorize()` against the policy instead.

Separately, "is this account even a shop/admin at all" is **not** the policy's job — every `BookController` route lives behind the `auth` + `role:admin` route middleware (see `app/Http/Middleware/EnsureUserHasRole.php`), which checks `App\Enums\UserRole`. Don't duplicate a role check inside `BookPolicy`; it should only ever compare `$book->user_id`.

`resources/views/components/book-card.blade.php` independently checks ownership to decide whether to show the edit/delete buttons — display-time convenience, not an authorization boundary (`BookController` still calls `Gate::authorize()` on the real actions).

Since `book.new` (`/new`) became a publicly viewable route (guest/user/admin, see `.ai/rules/routes.md`), that check must also require `auth()->user()->isAdmin()`, not just an ID match — otherwise a non-admin whose id happens to equal a book's `user_id` would incorrectly see edit/delete. The component now checks `auth()->check() && auth()->user()->isAdmin() && auth()->id() === $book->user_id`. `resources/views/book/new.blade.php` also gives the slot an add-to-cart form for any non-admin viewer, since that page is no longer admin-only.

The price / stock / add-to-cart form is the shared `<x-book-purchase-actions :book="$book" />` component (`resources/views/components/book-purchase-actions.blade.php`), used by `book/new`, `book/search`, and `store/index`. It reads `$book->stock`, so the controller must `with('stock')`. It always renders the form; the caller decides whether to show it (e.g. `@unless` admin).
