<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-sans">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <header class="flex items-center justify-between py-6">
                    <div class="flex items-center gap-2">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                        <span class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __('Finding Forrester') }}</span>
                    </div>

                    @if (Route::has('login'))
                        <livewire:welcome.navigation />
                    @endif
                </header>

                <main>
                    <!-- Hero -->
                    <section class="py-12 text-center sm:py-16">
                        <h1 class="text-4xl font-bold text-gray-900 dark:text-gray-100 sm:text-6xl">
                            {{ __('偶然の一冊が、人生を豊かにする。') }}
                        </h1>
                        <p class="mt-4 text-base text-gray-600 dark:text-gray-400 sm:text-lg">
                            {{ __('本を見つける。自分を見つける。') }}
                        </p>

                        <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                            @if (auth()->check())
                                @if (auth()->user()->isAdmin())
                                    <a href="{{ route('search') }}" wire:navigate>
                                        <x-primary-button>{{ __('書籍を探す') }}</x-primary-button>
                                    </a>
                                    <a href="{{ route('book.create') }}" wire:navigate>
                                        <x-secondary-button>{{ __('本を登録する') }}</x-secondary-button>
                                    </a>
                                    <a href="{{ route('book.new') }}" wire:navigate>
                                        <x-secondary-button>{{ __('入荷本一覧へ') }}</x-secondary-button>
                                    </a>
                                @else
                                    <a href="{{ route('store.index') }}" wire:navigate>
                                        <x-primary-button>{{ __('本屋へ') }}</x-primary-button>
                                    </a>
                                @endif
                            @else
                                <a href="{{ route('register') }}" wire:navigate>
                                    <x-secondary-button>{{ __('アカウント新規登録') }}</x-secondary-button>
                                </a>
                                <a href="{{ route('book.new') }}" wire:navigate>
                                    <x-secondary-button>{{ __('入荷本一覧へ') }}</x-secondary-button>
                                </a>
                            @endif
                        </div>
                    </section>
                </main>

                <footer class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ __('remixed by re:vue') }}
                </footer>
            </div>
        </div>
    </body>
</html>
