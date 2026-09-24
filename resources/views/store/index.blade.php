<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('本屋') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="get" action="{{ route('store.index') }}">
                <x-input-label for="type" :value="__('ジャンル')" />
                <select id="type" name="type" onchange="this.form.submit()"
                    class="mt-1 block w-48 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                    <option value="">{{ __('すべて') }}</option>
                    @foreach ($types as $genre)
                        <option value="{{ $genre }}" @selected($type === $genre)>{{ $genre }}</option>
                    @endforeach
                </select>
            </form>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse ($books as $book)
                    @php $stockCount = $book->stock?->stock ?? 0; @endphp
                    <x-book-card :book="$book">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">¥{{ number_format($book->price) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('在庫') }}: {{ $stockCount }}</p>
                        </div>
                        <form method="post" action="{{ route('cart.store') }}" class="mt-2">
                            @csrf
                            <input type="hidden" name="book_id" value="{{ $book->id }}">
                            <input type="hidden" name="quantity" value="1">
                            <x-primary-button class="w-full justify-center" :disabled="$stockCount < 1">
                                {{ $stockCount < 1 ? __('在庫切れ') : __('カートに追加') }}
                            </x-primary-button>
                        </form>
                    </x-book-card>
                @empty
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('該当する本が見つかりませんでした。') }}</p>
                @endforelse
            </div>

            {{ $books->links() }}
        </div>
    </div>
</x-app-layout>
