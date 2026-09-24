@php
    $types = ['未分類', 'ホラー', 'ミステリー', 'アクション', '恋愛', 'SF', '歴史', '自伝'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('編集画面') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <form method="post" action="{{ route('book.update', ['book' => $book->id]) }}" class="space-y-6">
                    @method('PUT')
                    @csrf

                    <div>
                        <x-input-label for="title" :value="__('タイトル')" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $book->title)" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('title')" />
                    </div>

                    <div>
                        <x-input-label for="author" :value="__('著者')" />
                        <x-text-input id="author" name="author" type="text" class="mt-1 block w-full" :value="old('author', $book->author)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('author')" />
                    </div>

                    <div>
                        <x-input-label for="type" :value="__('ジャンル')" />
                        <select id="type" name="type" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required>
                            <option value="">{{ __('--ジャンルを選んでください--') }}</option>
                            @foreach ($types as $type)
                                <option value="{{ $type }}" @selected(old('type', $book->type) === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('type')" />
                    </div>

                    <div>
                        <x-input-label for="stock" :value="__('在庫数')" />
                        <x-text-input id="stock" name="stock" type="number" min="0" class="mt-1 block w-full" :value="old('stock', $book->stock?->stock ?? 0)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('stock')" />
                    </div>

                    <div>
                        <x-input-label for="price" :value="__('価格')" />
                        <x-text-input id="price" name="price" type="number" min="0" class="mt-1 block w-full" :value="old('price', $book->price)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('price')" />
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="is_for_sale" name="is_for_sale" type="checkbox" value="1" @checked(old('is_for_sale', $book->is_for_sale))
                            class="rounded border-gray-300 dark:border-gray-700 text-indigo-600">
                        <x-input-label for="is_for_sale" :value="__('本屋で販売する')" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('更新') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <a href="{{ route('book.show', ['book' => $book->id]) }}">
                <x-secondary-button>{{ __('詳細画面へ') }}</x-secondary-button>
            </a>
        </div>
    </div>
</x-app-layout>
