<?php

namespace Savks\ESearch\Providers;

use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

use Savks\ESearch\{
    Manager\Manager,
    Support\ErrorsHandler,
    Commands
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
        $this->app->singleton('e-search.logger', function (Application $app) {
            $config = $app['config']->get('e-search.connection');

            $logger = new Logger('e-search');

            $logger->pushHandler(
                new StreamHandler($config['logging']['path'], $config['logging']['level'])
            );

            return $logger;
        });

        $this->app->singleton('e-search', function (Application $app) {
            $config = $app['config']->get('e-search.connection');

            $client = ClientBuilder::create()->setHosts($config['hosts']);

            if (isset($config['retries'])) {
                $client->setRetries($config['retries']);
            }

            if (! empty($config['logging']['enabled'])) {
                $client->setLogger($app['e-search.logger']);
            }

            return new Manager(
                $app,
                $client->build()
            );
        });

        $this->app->singleton('e-search.errors-handler', ErrorsHandler::class);
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
