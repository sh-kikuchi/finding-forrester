<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Volt\Volt;

/**
 * Livewire Volt（単一ファイルコンポーネント）の読み込み先を登録するサービスプロバイダー。
 */
class VoltServiceProvider extends ServiceProvider
{
    /**
     * サービスを登録する。
     */
    public function register(): void
    {
        //
    }

    /**
     * Voltコンポーネントを読み込むディレクトリを登録する。
     */
    public function boot(): void
    {
        Volt::mount([
            config('livewire.view_path', resource_path('views/livewire')),
            resource_path('views/pages'),
        ]);
    }
}
