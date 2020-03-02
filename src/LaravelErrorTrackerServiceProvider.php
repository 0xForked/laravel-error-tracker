<?php

namespace PollieDev\LaravelErrorTracker;

use Illuminate\Support\ServiceProvider;
use Monolog\Logger;
use Throwable;

class LaravelErrorTrackerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('error-tracker.php'),
            ], 'config');

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
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'error-tracker');

        $this->app['log']->pushProcessor(function($record) {
            if (isset($record['context']['exception']) && $record['context']['exception'] instanceof Throwable) {
                LaravelErrorTracker::Report($record['context']['exception']);
            }
            return $record;
        });
    }
}
