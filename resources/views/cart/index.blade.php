<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('カート') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ session('status') }}</p>
            @endif

            @if ($books->isEmpty())
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('カートに商品がありません。') }}</p>
            @else
                <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($items as $bookId => $quantity)
                        @continue(! isset($books[$bookId]))
                        @php $book = $books[$bookId]; @endphp
                        <div class="p-4 flex items-center gap-4">
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-gray-100 line-clamp-1">{{ $book->title }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">¥{{ number_format($book->price) }}</p>
                            </div>

                            <form method="post" action="{{ route('cart.update', ['book' => $book->id]) }}" class="flex items-center gap-2">
                                @method('PATCH')
                                @csrf
                                <input type="number" name="quantity" value="{{ $quantity }}" min="1" max="{{ $book->stock?->stock ?? 0 }}"
                                    class="w-20 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <x-secondary-button>{{ __('更新') }}</x-secondary-button>
                            </form>

                            <form method="post" action="{{ route('cart.destroy', ['book' => $book->id]) }}">
                                @method('DELETE')
                                @csrf
                                <x-danger-button>{{ __('削除') }}</x-danger-button>
                            </form>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-between">
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('合計') }}: ¥{{ number_format($total) }}</p>
                    <a href="{{ route('checkout.show') }}">
                        <x-primary-button>{{ __('チェックアウトへ進む') }}</x-primary-button>
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
