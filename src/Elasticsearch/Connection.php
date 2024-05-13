<?php

namespace Savks\ESearch\Elasticsearch;

use Elastic\Elasticsearch\{
    Client,
    ClientBuilder
};
use Illuminate\Support\{
    Arr,
    Str
};
use Monolog\{
    Handler\StreamHandler,
    Logger
};

class Connection
{
    public readonly bool $isTrackPerformanceEnabled;

    protected Logger $logger;

    protected Client $client;

    protected ErrorsHandler $errorsHandler;

    public function __construct(
        public readonly string $name,
        public readonly array $config
    ) {
        $this->isTrackPerformanceEnabled = (bool)($config['enable_track_performance'] ?? false);
    }

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

    public function resolveIndexSettings(string $indexName, ?string $settingName = null): mixed
    {
        $prefixedIndexName = $this->resolveIndexName($indexName);

        $response = $this->client()->indices()->getSettings([
            'index' => $prefixedIndexName,
        ]);

        if (! $settingName) {
            return $response[$prefixedIndexName]['settings'];
        }

        return Arr::get($response, "{$prefixedIndexName}.settings.{$settingName}");
    }
}
