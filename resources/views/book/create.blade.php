@php
    $types = ['未分類', 'ホラー', 'ミステリー', 'アクション', '恋愛', 'SF', '歴史', '自伝'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('新しい本の登録') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <form method="post" action="{{ route('book.create') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="title" :value="__('Title')" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" placeholder="{{ __('タイトルを入れてください') }}" :value="old('title')" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('title')" />
                    </div>

                    <div>
                        <x-input-label for="author" :value="__('著者')" />
                        <x-text-input id="author" name="author" type="text" class="mt-1 block w-full" placeholder="{{ __('著者を入れてください') }}" :value="old('author')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('author')" />
                    </div>

                    <div>
                        <x-input-label for="type" :value="__('ジャンル')" />
                        <select id="type" name="type" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required>
                            <option value="">{{ __('--ジャンルを選んでください--') }}</option>
                            @foreach ($types as $type)
                                <option value="{{ $type }}" @selected(old('type') === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('type')" />
                    </div>

                    <div>
                        <x-input-label for="stock" :value="__('入荷数')" />
                        <x-text-input id="stock" name="stock" type="number" min="0" class="mt-1 block w-full" :value="old('stock')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('stock')" />
                    </div>

                    <div>
                        <x-input-label for="price" :value="__('価格')" />
                        <x-text-input id="price" name="price" type="number" min="0" class="mt-1 block w-full" :value="old('price')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('price')" />
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="is_for_sale" name="is_for_sale" type="checkbox" value="1" @checked(old('is_for_sale', true))
                            class="rounded border-gray-300 dark:border-gray-700 text-indigo-600">
                        <x-input-label for="is_for_sale" :value="__('本屋で販売する')" />
                    </div>

                    <div>
                        <x-input-label for="image" :value="__('画像')" />
                        @if (old('google_image_url'))
                            <div class="mt-1 flex items-center gap-3">
                                <img src="{{ old('google_image_url') }}" alt="" class="w-16 h-16 object-cover rounded bg-gray-100 dark:bg-gray-900">
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Googleから取得した画像を使用します。別の画像をアップロードすると、そちらが優先されます。') }}</p>
                            </div>
                            <input type="hidden" name="google_image_url" value="{{ old('google_image_url') }}">
                        @endif
                        <input id="image" name="image" type="file" class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300" />
                        <x-input-error class="mt-2" :messages="$errors->get('image')" />
                        <x-input-error class="mt-2" :messages="$errors->get('google_image_url')" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('追加') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
