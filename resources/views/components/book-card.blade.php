@props(['book'])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-400 shadow sm:rounded-lg overflow-hidden']) }}>
    <div class="flex gap-4 p-3">
        @if ($book->image_url)
            <img
                src="{{ $book->image_url }}"
                alt="{{ $book->title }}"
                class="w-32 h-40 shrink-0 object-cover bg-gray-100 dark:bg-gray-900 rounded"
            >
        @else
            <div
                role="img"
                aria-label="{{ __('画像が設定されていません') }}"
                class="w-24 h-32 shrink-0 flex items-center justify-center bg-gray-100 dark:bg-gray-900 rounded"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-10 h-10 text-gray-300 dark:text-gray-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
            </div>
        @endif

        <div class="min-w-0 flex-1 space-y-1">
            <p class="font-semibold text-gray-900 dark:text-gray-100 line-clamp-1">{{ $book->title }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-1">{{ $book->author }} ・ {{ $book->type }}</p>

            @if(auth()->check() && auth()->user()->isAdmin() && auth()->id() === $book->user_id)
                <div class="pt-2 flex items-center gap-2">
                    <a href="{{ route('book.edit', ['book'=> $book->id]) }}">
                        <x-secondary-button>{{ __('編集') }}</x-secondary-button>
                    </a>
                    <form method="post" action="{{ route('book.destroy', ['book' => $book->id]) }}" onsubmit="return confirm('{{ __('本当に削除しますか?') }}')">
                        @method('DELETE')
                        @csrf
                        <x-danger-button>{{ __('削除') }}</x-danger-button>
                    </form>
                </div>
            @endif

            @if ($slot->isNotEmpty())
                <div class="pt-2">
                    {{ $slot }}
                </div>
            @endif
        </div>
    </div>
</div>
