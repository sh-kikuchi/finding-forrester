<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('新しい本を探す') }}
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
                            <x-book-purchase-actions :book="$book" />
                        @endunless
                    </x-book-card>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
