<?php

namespace HiFolks\Fusion;

use HiFolks\Fusion\Console\Commands\CheckMarkdown;
use HiFolks\Fusion\Console\Commands\CheckModel;
use HiFolks\Fusion\Console\Commands\SyncModel;
use Illuminate\Support\ServiceProvider;

class FusionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'fusion');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'fusion');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('fusion.php'),
            ], 'config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/fusion'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/fusion'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/fusion'),
            ], 'lang');*/

            // Registering package commands.
            $this->commands([
                CheckMarkdown::class,
                CheckModel::class,
                SyncModel::class,
            ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'fusion');

        // Register the main class to use with the facade
        $this->app->singleton('fusion', static fn (): \HiFolks\Fusion\Fusion => new Fusion);
    }
}
