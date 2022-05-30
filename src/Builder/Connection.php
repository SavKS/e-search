<?php

namespace Savks\ESearch\Builder;

use Elastic\Elasticsearch\{
    Exception\AuthenticationException,
    Client,
    ClientBuilder
};
use Monolog\{
    Handler\StreamHandler,
    Logger
};
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Savks\ESearch\Elasticsearch\ErrorsHandler;

class Connection
{
    /**
     * @var string
     */
    public readonly string $name;

    /**
     * @var array
     */
    public readonly array $config;

    /**
     * @var bool
     */
    public readonly bool $isTrackPerformanceEnabled;

    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * @var Client
     */
    protected Client $client;

    /**
     * @var ErrorsHandler
     */
    protected ErrorsHandler $errorsHandler;

    /**
     * @param string $name
     * @param array $config
     */
    public function __construct(string $name, array $config)
    {
        $this->name = $name;
        $this->config = $config;

        $this->isTrackPerformanceEnabled = (bool)($config['enable_track_performance'] ?? false);
    }

    /**
     * @param string $name
     * @return string
     */
    public function resolveIndexName(string $name): string
    {
        $prefix = $this->config['index_prefix'] ?? null;

        if ($prefix) {
            return sprintf(
                '%s_%s',
                Str::snake($prefix),
                $name
            );
        }

        return $name;
    }

    /**
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    public function config(string $path, mixed $default): mixed
    {
        return Arr::get($path, $this->config, $default);
    }

    /**
     * @return Logger
     */
    public function logger(): Logger
    {
        if (! isset($this->logger)) {
            $config = $this->config['connection'];

            $this->logger = new Logger("e-search--{$this->name}");

            $this->logger->pushHandler(
                new StreamHandler($config['logging']['path'], $config['logging']['level'])
            );
        }

        return $this->logger;
    }

    /**
     * @return Client
     * @throws AuthenticationException
     */
    public function client(): Client
    {
        if (! isset($this->client)) {
            $client = ClientBuilder::create()->setHosts($this->config['connection']['hosts']);

            if (isset($this->config['connection']['retries'])) {
                $client->setRetries($this->config['connection']['retries']);
            }

            if (! empty($this->config['connection']['logging']['enabled'])) {
                $client->setLogger(
                    $this->logger()
                );
            }

            $this->client = $client->build();
        }

        return $this->client;
    }

    /**
     * @return ErrorsHandler
     */
    public function errorsHandler(): ErrorsHandler
    {
        if (! isset($this->errorsHandler)) {
            $this->errorsHandler = new ErrorsHandler(
                $this->config,
                $this->logger()
            );
        }

        return $this->errorsHandler;
    }
}
