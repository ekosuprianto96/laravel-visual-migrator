<?php

namespace Ekosuprianto96\VisualMigrator;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class VisualMigratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->isLocal()) {
            $this->loadViewsFrom(__DIR__ . '/../resources/views', 'visual-migrator');
            $this->registerRoutes();
        }

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

            $this->publishes([
                __DIR__ . '/../config/visual-migrator.php' => config_path('visual-migrator.php'),
            ], 'visual-migrator-config');

            // Asset publishing from the package's internal dist folder
            // This folder is populated by running `npm run build:laravel` in the root
            $this->publishes([
                __DIR__ . '/../dist' => public_path('vendor/visual-migrator'),
            ], 'visual-migrator-assets');

            $this->commands([
                Console\Commands\InstallCommand::class,
                Console\Commands\CleanupCommand::class,
                Console\Commands\RefreshMetadataCommand::class,
            ]);
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/visual-migrator.php', 'visual-migrator');
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        });
    }

    /**
     * Get the route group configuration.
     */
    protected function routeConfiguration(): array
    {
        return [
            'prefix' => config('visual-migrator.path', 'visual-migrator'),
            'middleware' => config('visual-migrator.middleware', ['web']),
        ];
    }
}
