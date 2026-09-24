---
paths:
  - app/Http/Controllers/BookController.php
---

# Controllers

## Don't type-hint a FormRequest on an action that also handles GET
`BookController::create` handles both GET (show the form) and POST (submit it) on the same route (`Route::match(['get','post'], '/book/create', ...)`). Type-hinting `StoreBookRequest $request` directly on that method runs the FormRequest's validation on every request, including the GET — with no body, all "required" rules fail and the user gets silently redirected back instead of seeing the form. Fix: keep the plain `Request $request` signature and validate manually only inside the `isMethod('post')` branch via `$request->validate((new StoreBookRequest)->rules())`. If another combined GET/POST action is added, apply the same pattern rather than a FormRequest type-hint.

Also: `resources/views/book/search.blade.php` expects the Google Books API result as `$json_decode` (snake_case) — matching Blade variable names against what the view actually reads (not just `assertViewHas` in tests) caught a real mismatch bug once; `assertSee` on rendered content is what actually catches this class of defect.

Google Books search (`BookController::searchGoogleBooks`) now sends `key` from `config('services.google_books.key')` (env `GOOGLE_BOOKS_API_KEY`, optional) and shows a friendly `book.search` error message on HTTP failure or `ConnectionException`, instead of silently rendering nothing — the anonymous Google Books quota is low and shared, so 429s are common without a key.
