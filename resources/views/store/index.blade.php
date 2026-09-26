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
                    <x-book-card :book="$book">
                        <x-book-purchase-actions :book="$book" />
                    </x-book-card>
                @empty
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('該当する本が見つかりませんでした。') }}</p>
                @endforelse
            </div>

            {{ $books->links() }}
        </div>
    </div>
</x-app-layout>
