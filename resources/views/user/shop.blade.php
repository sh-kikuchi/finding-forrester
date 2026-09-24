<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('店舗情報') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg space-y-4">
                @if ($shop->image)
                    <img src="{{ asset('image/'.$shop->image) }}" alt="" class="h-24 w-24 object-cover rounded-md">
                @endif

                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('店舗名') }}</p>
                    <p class="text-gray-900 dark:text-gray-100">{{ $shop->name }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('メール') }}</p>
                    <p class="text-gray-900 dark:text-gray-100">{{ $shop->email }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('住所') }}</p>
                    <p class="text-gray-900 dark:text-gray-100">{{ $shop->address }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('電話番号') }}</p>
                    <p class="text-gray-900 dark:text-gray-100">{{ $shop->tel }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('営業時間') }}</p>
                    <p class="text-gray-900 dark:text-gray-100">{{ $shop->time }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
