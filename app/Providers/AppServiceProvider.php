<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        # ensure you configure the right channel you use
        config(['logging.channels.single.path' => \Phar::running()
            ? dirname(\Phar::running(false)) . '/poortman.log'
            : storage_path('logs/poortman.log')
        ]);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
