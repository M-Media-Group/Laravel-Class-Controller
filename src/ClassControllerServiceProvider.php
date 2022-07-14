<?php

namespace MMedia\ClassController;

use Illuminate\Support\ServiceProvider;
use MMedia\ClassController\Http\Controllers\ClassController;

class ClassControllerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'classcontroller');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'classcontroller');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('classcontroller.php'),
            ], 'config');

            // Publish the stubs
            $this->publishes([
                __DIR__ . '/../stubs' => base_path('stubs'),
            ], 'classcontroller-stubs');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/classcontroller'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/classcontroller'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/classcontroller'),
            ], 'lang');*/

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'classcontroller');

        // Register the main class to use with the facade
        $this->app->singleton('classcontroller', function () {
            return new ClassController();
        });
    }
}
