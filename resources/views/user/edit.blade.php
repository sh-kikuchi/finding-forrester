<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('ショップ情報編集') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <form method="post" action="{{ route('shop.update') }}" enctype="multipart/form-data" class="space-y-6">
                    @method('PUT')
                    @csrf

                    <div>
                        <x-input-label for="name" :value="__('店舗名')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $shop->name)" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('メールアドレス')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $shop->email)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>

                    <div>
                        <x-input-label for="newPassword" :value="__('新しいパスワード')" />
                        <x-text-input id="newPassword" name="newPassword" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('変更しない場合は空欄のままにしてください') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('newPassword')" />
                    </div>

                    <div>
                        <x-input-label for="address" :value="__('住所')" />
                        <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address', $shop->address)" />
                        <x-input-error class="mt-2" :messages="$errors->get('address')" />
                    </div>

                    <div>
                        <x-input-label for="tel" :value="__('電話番号')" />
                        <x-text-input id="tel" name="tel" type="tel" class="mt-1 block w-full" :value="old('tel', $shop->tel)" />
                        <x-input-error class="mt-2" :messages="$errors->get('tel')" />
                    </div>

                    <div>
                        <x-input-label for="time" :value="__('営業時間')" />
                        <x-text-input id="time" name="time" type="text" class="mt-1 block w-full" :value="old('time', $shop->time)" />
                        <x-input-error class="mt-2" :messages="$errors->get('time')" />
                    </div>

                    <div>
                        <x-input-label for="image" :value="__('イメージ画像')" />
                        @if ($shop->image)
                            <img src="{{ asset('image/'.$shop->image) }}" alt="" class="mt-2 h-24 w-24 object-cover rounded-md">
                        @endif
                        <input id="image" name="image" type="file" class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300" />
                        <x-input-error class="mt-2" :messages="$errors->get('image')" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
