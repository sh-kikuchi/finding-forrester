<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('本棚にストックする') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('過去1ヶ月に入荷した本を表示しています') }}
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($books as $book)
                    <x-book-card :book="$book">
                        @unless (auth()->check() && auth()->user()->isAdmin())
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
                        @endunless
                    </x-book-card>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
