<?php

namespace App\Providers;

use App\Poortman\Configuration;
use App\Poortman\Renamer;
use App\Poortman\SourceBuilder;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ensure you configure the right channel you use
        config(['logging.channels.single.path' => getcwd() . '/poortman.log']);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Configuration::class, fn() => new Configuration());
        $this->app->singleton(Renamer::class, fn() => new Renamer());
        $this->app->singleton(SourceBuilder::class, fn() => new SourceBuilder());
    }
}
