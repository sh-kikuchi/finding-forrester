<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('本を見つける') }}
        </h2>
    </x-slot>

    @php $isAdmin = auth()->check() && auth()->user()->isAdmin(); @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div x-data="{ tab: '{{ isset($json_decode) || isset($googleSearchError) ? 'google' : 'shop' }}' }" class="max-w-xl">
                @if ($isAdmin)
                    <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700 mb-4">
                        <button
                            type="button"
                            @click="tab = 'shop'"
                            :class="tab === 'shop' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400'"
                            class="px-4 py-2 text-sm font-medium border-b-2 -mb-px"
                        >
                            {{ __('書店から本を検索') }}
                        </button>
                        <button
                            type="button"
                            @click="tab = 'google'"
                            :class="tab === 'google' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400'"
                            class="px-4 py-2 text-sm font-medium border-b-2 -mb-px"
                        >
                            {{ __('GoogleBookから本を検索') }}
                        </button>
                    </div>
                @endif

                <div x-show="tab === 'shop'" class="p-4 sm:p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <form method="get" action="{{ route('search') }}" class="flex gap-2">
                        <x-text-input name="q" type="text" class="block w-full" :value="$key ?? ''" placeholder="{{ __('どの本をお探しですか？') }}" />
                        <x-primary-button>{{ __('検索') }}</x-primary-button>
                    </form>
                </div>

                @if ($isAdmin)
                    <div x-show="tab === 'google'" class="p-4 sm:p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                        <form method="get" action="{{ route('book.searchGoogle') }}" class="flex gap-2">
                            <x-text-input name="q" type="text" class="block w-full" placeholder="{{ __('どの本をお探しですか？') }}" />
                            <x-primary-button>{{ __('検索') }}</x-primary-button>
                        </form>
                    </div>
                @endif
            </div>

            @isset($key)
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ "『{$key}』に一致するものを表示" }}
                </p>
            @endisset

            @isset($books)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse ($books as $book)
                        <x-book-card :book="$book">
                            @unless ($isAdmin)
                                <div class="space-y-2">
                                    <a href="{{ route('shop.show', ['shop' => $book->user_id]) }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ __('取扱店舗') }}
                                    </a>
                                    <x-book-purchase-actions :book="$book" />
                                </div>
                            @endunless
                        </x-book-card>
                    @empty
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('該当する本が見つかりませんでした。') }}</p>
                    @endforelse
                </div>

                {{ $books->links() }}
            @endisset

            @isset($googleSearchError)
                <p class="text-sm text-red-600 dark:text-red-400">
                    {{ $googleSearchError }}
                </p>
            @endisset

            @isset($json_decode['items'])
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($json_decode['items'] as $item)
                        <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden">
                            @if (isset($item['volumeInfo']['imageLinks']['smallThumbnail']))
                                <img src="{{ $item['volumeInfo']['imageLinks']['smallThumbnail'] }}" alt="" class="w-full h-48 object-contain bg-gray-100 dark:bg-gray-900">
                            @endif

                            <div class="p-4 space-y-2">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('タイトル') }}</p>
                                    <p class="text-gray-900 dark:text-gray-100 font-semibold">{{ $item['volumeInfo']['title'] }}</p>
                                </div>

                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('著者') }}</p>
                                    <p class="text-gray-900 dark:text-gray-100">
                                        {{ $item['volumeInfo']['authors'][0] ?? __('著者不明') }}
                                    </p>
                                </div>
                                @auth
                                    <form method="post" action="{{ route('book.create.fromGoogle') }}">
                                        @csrf
                                        <input type="hidden" name="title" value="{{ $item['volumeInfo']['title'] ?? '' }}">
                                        <input type="hidden" name="author" value="{{ $item['volumeInfo']['authors'][0] ?? __('著者不明') }}">
                                        <input type="hidden" name="image" value="{{ $item['volumeInfo']['imageLinks']['smallThumbnail'] ?? '' }}">
                                        <x-primary-button type="submit">{{ __('この本を登録する') }}</x-primary-button>
                                    </form>
                                @else
                                    <a href="{{ route('login') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ __('登録するにはログインしてください') }}
                                    </a>
                                @endauth
                            </div>
                        </div>
                    @endforeach
                </div>
            @endisset
        </div>
    </div>
</x-app-layout>
