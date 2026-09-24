---
paths:
  - routes/web.php
---

# Routes

## Keep wildcard /shop/{shop} registered after /shop/edit
`Route::get('/shop/{shop}', ...)->name('shop.show')` must stay registered AFTER the `auth` group containing `/shop/edit`. Laravel matches routes in file order, so if the wildcard route comes first, a GET to `/shop/edit` gets swallowed by `{shop}` (implicit User binding on the literal string "edit" fails → 404) instead of reaching UserController::edit. Caught by tests/Feature/UserControllerTest.php.

## /new (book.new) is intentionally public, not admin-only
Unlike the rest of `BookController`, `Route::get('/new', ...)->name('book.new')` carries no `auth`/`role` middleware — confirmed requirement: guests, individual users, and admins must all be able to view it. Don't move it back inside the `auth`+`role:admin` group. See `.ai/rules/http-controllers.md` for the corresponding view-side changes (`book-card.blade.php` ownership check, `book/new.blade.php` add-to-cart slot) this requires.
