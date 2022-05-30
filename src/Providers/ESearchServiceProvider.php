<?php

namespace Savks\ESearch\Providers;

use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

use Savks\ESearch\{
    Manager\Manager,
    Elasticsearch\ErrorsHandler,
    Commands,
    Manager\ResourcesRepository
};
use Monolog\{
    Handler\StreamHandler,
    Logger
};

class ESearchServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(
            Manager::class,
            fn() => new Manager()
        );

        $this->app->singleton(ResourcesRepository::class, function (Application $app) {
            return new ResourcesRepository(
                $app['config']->get('e-search.resources', [])
            );
        });
    }

    /**
     * @return void
     */
    public function boot()
    {
        $this->publishConfigs();

        $this->commands([
            Commands\Clear::class,
            Commands\Init::class,
            Commands\Seed::class,
            Commands\Sync::class,
            Commands\Truncate::class,
            Commands\Reset::class,
            Commands\UpdatesRun::class,
        ]);

        $this->loadMigrationsFrom(
            \dirname(__DIR__, 2) . '/database/migrations'
        );
    }

    /**
     * @return void
     */
    protected function publishConfigs(): void
    {
        $source = \dirname(__DIR__, 2) . '/resources/configs/e-search.php';

        $this->mergeConfigFrom($source, 'e-search');

        $this->publishes([
            $source => \config_path('e-search.php'),
        ], 'configs');
    }
}
