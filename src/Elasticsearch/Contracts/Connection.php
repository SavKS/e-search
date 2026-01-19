<?php

namespace Savks\ESearch\Elasticsearch\Contracts;

use Elastic\Elasticsearch\Client;
use Monolog\Logger;
use Savks\ESearch\Elasticsearch\ErrorsHandler;

interface Connection
{
    public function resolveIndexName(string $name): string;

    public function logger(): Logger;

    public function client(): Client;

    public function errorsHandler(): ErrorsHandler;

    public function resolveIndexSettings(string $indexName, ?string $settingName = null): mixed;
}
