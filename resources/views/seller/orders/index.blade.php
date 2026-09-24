<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('受注一覧') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ session('status') }}</p>
            @endif

            @forelse ($orders as $order)
                <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-gray-900 dark:text-gray-100">{{ __('注文') }} #{{ $order->id }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->created_at->format('Y-m-d H:i') }}</p>
                    </div>
                    @foreach ($order->items as $item)
                        <div class="flex items-center justify-between">
                            <p class="text-gray-900 dark:text-gray-100">{{ $item->title }} × {{ $item->quantity }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">¥{{ number_format($item->price * $item->quantity) }}</p>
                        </div>
                    @endforeach
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('購入者') }}: {{ $order->user->name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('小計') }} ¥{{ number_format($order->total()) }}</p>
                    </div>
                    <div class="mt-2 flex items-center justify-end">
                        @if ($order->items->first()->shipped_at)
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('発送済み') }}（{{ $order->items->first()->shipped_at->format('Y-m-d H:i') }}）</p>
                        @else
                            <form method="POST" action="{{ route('shop.orders.ship', $order) }}">
                                @csrf
                                <x-primary-button>{{ __('発送する') }}</x-primary-button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('受注がありません。') }}</p>
            @endforelse

            {{ $orders->links() }}
        </div>
    </div>
</x-app-layout>
