<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    /**
     * ログイン中のユーザーが管理者（店舗）ロールかどうか。
     */
    public function isAdmin(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    /**
     * 本屋を閲覧できるか（ゲスト、または個人ユーザー。管理者は不可）。
     */
    public function canBrowseStore(): bool
    {
        return ! auth()->check() || auth()->user()->isUser();
    }
}; ?>

<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ $this->isAdmin() ? route('book.new') : (auth()->check() ? route('store.index') : url('/')) }}" wire:navigate>
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @if ($this->canBrowseStore())
                        <x-nav-link :href="route('store.index')" :active="request()->routeIs('store.index')" wire:navigate>
                            {{ __('本屋') }}
                        </x-nav-link>
                        <x-nav-link :href="route('cart.index')" :active="request()->routeIs('cart.index')" wire:navigate>
                            {{ __('カート') }}
                        </x-nav-link>
                    @endif
                    @if ($this->isAdmin())
                        <x-nav-link :href="route('search')" :active="request()->routeIs('search')" wire:navigate>
                            {{ __('本を見つける') }}
                        </x-nav-link>
                        <x-nav-link :href="route('book.create')" :active="request()->routeIs('book.create')" wire:navigate>
                            {{ __('新しい本を登録する') }}
                        </x-nav-link>
                        <x-nav-link :href="route('book.new')" :active="request()->routeIs('book.new')" wire:navigate>
                            {{ __('本棚にストックする') }}
                        </x-nav-link>
                        <x-nav-link :href="route('shop.orders.index')" :active="request()->routeIs('shop.orders.index')" wire:navigate>
                            {{ __('受注一覧') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                                <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>

                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile')" wire:navigate>
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            @unless ($this->isAdmin())
                                <x-dropdown-link :href="route('orders.index')" wire:navigate>
                                    {{ __('注文履歴') }}
                                </x-dropdown-link>
                            @endunless

                            @if ($this->isAdmin())
                                <x-dropdown-link :href="route('shop.edit')" wire:navigate>
                                    {{ __('ショップ情報を編集') }}
                                </x-dropdown-link>
                            @endif

                            <!-- Authentication -->
                            <button wire:click="logout" class="w-full text-start">
                                <x-dropdown-link>
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </button>
                        </x-slot>
                    </x-dropdown>
                @else
                    <div class="flex items-center gap-4">
                        <x-nav-link :href="route('login')" :active="request()->routeIs('login')" wire:navigate>
                            {{ __('Login') }}
                        </x-nav-link>
                        <x-nav-link :href="route('register')" :active="request()->routeIs('register')" wire:navigate>
                            {{ __('Register') }}
                        </x-nav-link>
                    </div>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @if ($this->canBrowseStore())
                <x-responsive-nav-link :href="route('store.index')" :active="request()->routeIs('store.index')" wire:navigate>
                    {{ __('本屋') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('cart.index')" :active="request()->routeIs('cart.index')" wire:navigate>
                    {{ __('カート') }}
                </x-responsive-nav-link>
            @endif
            @if ($this->isAdmin())
                <x-responsive-nav-link :href="route('search')" :active="request()->routeIs('search')" wire:navigate>
                    {{ __('本を見つける') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('book.new')" :active="request()->routeIs('book.new')" wire:navigate>
                    {{ __('本棚にストックする') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('book.create')" :active="request()->routeIs('book.create')" wire:navigate>
                    {{ __('新しい本を登録する') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('shop.orders.index')" :active="request()->routeIs('shop.orders.index')" wire:navigate>
                    {{ __('受注一覧') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            @auth
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800 dark:text-gray-200" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                    <div class="font-medium text-sm text-gray-500">{{ auth()->user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile')" wire:navigate>
                        {{ __('Profile') }}
                    </x-responsive-nav-link>

                    @unless ($this->isAdmin())
                        <x-responsive-nav-link :href="route('orders.index')" wire:navigate>
                            {{ __('注文履歴') }}
                        </x-responsive-nav-link>
                    @endunless

                    @if ($this->isAdmin())
                        <x-responsive-nav-link :href="route('shop.edit')" wire:navigate>
                            {{ __('ショップ情報を編集') }}
                        </x-responsive-nav-link>
                    @endif

                    <!-- Authentication -->
                    <button wire:click="logout" class="w-full text-start">
                        <x-responsive-nav-link>
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </button>
                </div>
            @else
                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('login')" wire:navigate>
                        {{ __('Login') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('register')" wire:navigate>
                        {{ __('Register') }}
                    </x-responsive-nav-link>
                </div>
            @endauth
        </div>
    </div>
</nav>
