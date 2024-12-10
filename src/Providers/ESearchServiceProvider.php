<?php

namespace Savks\ESearch\Providers;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Savks\ESearch\Commands;
use Savks\ESearch\Debug\PerformanceTracker;
use Savks\ESearch\Elasticsearch\ConnectionsManager;
use Savks\ESearch\Resources\ResourcesRepository;

class ESearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ResourcesRepository::class,
            fn (Application $app) => new ResourcesRepository(
                $app['config']->get('e-search.resources', [])
            )
        );

        $this->app->singleton(
            ConnectionsManager::class,
            fn (Application $app) => new ConnectionsManager(
                $app['config']->get('e-search.connections', []),
                $app['config']->get('e-search.default_connection', []),
            )
        );

        $this->app->singleton(PerformanceTracker::class, function (Application $app) {
            $trackerFQN = $app['config']->get('e-search.performance_tracker');

            return new $trackerFQN();
        });
    }

    public function boot(): void
    {
        $this->publishConfigs();

        $this->commands([
            Commands\Fill::class,
            Commands\Truncate::class,
            Commands\RemoveRedundantIndices::class,
        ]);

        $this->loadMigrationsFrom(
            dirname(__DIR__, 2) . '/database/migrations'
        );
    }

    protected function publishConfigs(): void
    {
        $source = dirname(__DIR__, 2) . '/resources/configs/e-search.php';

        $this->mergeConfigFrom($source, 'e-search');

        $this->publishes([
            $source => config_path('e-search.php'),
        ], 'configs');
    }
}
