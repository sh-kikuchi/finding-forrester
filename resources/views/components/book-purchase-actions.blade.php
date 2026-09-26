@props(['book'])

@php $stockCount = $book->stock?->stock ?? 0; @endphp

<div class="flex items-center justify-between gap-3">
    <p class="font-semibold text-gray-900 dark:text-gray-100">¥{{ number_format($book->price) }}</p>
    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('在庫') }}: {{ $stockCount }}</p>
</div>
<form method="post" action="{{ route('cart.store') }}" class="mt-2">
    @csrf
    <input type="hidden" name="book_id" value="{{ $book->id }}">
    <input type="hidden" name="quantity" value="1">
    <x-primary-button class="w-full justify-center" :disabled="! $book->is_for_sale || $stockCount < 1">
        {{ ! $book->is_for_sale ? __('販売対象外') : ($stockCount < 1 ? __('在庫切れ') : __('カートに追加')) }}
    </x-primary-button>
</form>
