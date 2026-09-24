<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('注文') }} #{{ $order->id }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($order->items as $item)
                    <div class="p-4 flex items-center justify-between gap-4">
                        <p class="text-gray-900 dark:text-gray-100">{{ $item->title }} × {{ $item->quantity }}</p>
                        <p class="text-gray-500 dark:text-gray-400">¥{{ number_format($item->price * $item->quantity) }}</p>
                    </div>
                @endforeach
            </div>

            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('合計') }}: ¥{{ number_format($order->total()) }}</p>
        </div>
    </div>
</x-app-layout>
