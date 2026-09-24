<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('チェックアウト') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @error('cart')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($items as $bookId => $quantity)
                    @continue(! isset($books[$bookId]))
                    @php $book = $books[$bookId]; @endphp
                    <div class="p-4 flex items-center justify-between gap-4">
                        <p class="text-gray-900 dark:text-gray-100">{{ $book->title }} × {{ $quantity }}</p>
                        <p class="text-gray-500 dark:text-gray-400">¥{{ number_format($book->price * $quantity) }}</p>
                    </div>
                @endforeach
            </div>

            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('合計') }}: ¥{{ number_format($total) }}</p>

            <form method="post" action="{{ route('checkout.store') }}">
                @csrf
                <x-primary-button>{{ __('注文を確定する') }}</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
