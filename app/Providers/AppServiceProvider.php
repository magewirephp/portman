<?php

namespace App\Providers;

use App\Portman\Configuration\ConfigurationLoader;
use App\Portman\Renamer;
use App\Portman\SourceBuilder;
use App\Portman\TransformerConfiguration;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ensure you configure the right channel you use
        config(['logging.channels.single.path' => getcwd() . '/portman.log']);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ConfigurationLoader::class, fn() => new ConfigurationLoader());
        $this->app->singleton(TransformerConfiguration::class, fn() => new TransformerConfiguration());
        $this->app->singleton(Renamer::class, fn() => new Renamer());
        $this->app->singleton(SourceBuilder::class, fn() => new SourceBuilder());
    }
}
