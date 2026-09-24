<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('注文履歴') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @forelse ($orders as $order)
                <a href="{{ route('orders.show', $order) }}" class="block bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-gray-900 dark:text-gray-100">{{ __('注文') }} #{{ $order->id }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->created_at->format('Y-m-d H:i') }}</p>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">¥{{ number_format($order->total()) }}</p>
                </a>
            @empty
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('注文履歴がありません。') }}</p>
            @endforelse

            {{ $orders->links() }}
        </div>
    </div>
</x-app-layout>
