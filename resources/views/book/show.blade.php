<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('詳細画面') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg space-y-4">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('タイトル') }}</p>
                    <p class="text-gray-900 dark:text-gray-100 font-semibold">{{ $book->title }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('著者') }}</p>
                    <p class="text-gray-900 dark:text-gray-100">{{ $book->author }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('ジャンル') }}</p>
                    <p class="text-gray-900 dark:text-gray-100">{{ $book->type }}</p>
                </div>
            </div>

            <a href="{{ route('book.edit', ['book' => $book->id]) }}">
                <x-secondary-button>{{ __('編集') }}</x-secondary-button>
            </a>
        </div>
    </div>
</x-app-layout>
