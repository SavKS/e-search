<?php

namespace Savks\ESearch\Elasticsearch;

use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;
use Illuminate\Support\Arr;
use Monolog\Logger;
use Savks\ESearch\Exceptions\BaseException;
use Savks\ESearch\Exceptions\BulkSaveFail;
use Savks\ESearch\Exceptions\OperationFail;
use Savks\ESearch\Exceptions\SaveFail;

class ErrorsHandler
{
    protected bool $isErrorsHandlingDebugEnabled;

    protected bool $isErrorsHandlingWriteToLog;

    protected bool $isErrorsHandlingUseSentry;

    protected bool $isLoggingEnabled;

    public function __construct(
        protected readonly array $config,
        protected readonly Logger $logger
    ) {
        $this->isErrorsHandlingDebugEnabled = (bool)Arr::get($config, 'connection.errors_handling.debug_enabled');
        $this->isErrorsHandlingWriteToLog = (bool)Arr::get($config, 'connection.errors_handling.write_to_log');
        $this->isErrorsHandlingUseSentry = (bool)Arr::get($config, 'connection.errors_handling.use_sentry');
        $this->isLoggingEnabled = (bool)Arr::get($config, 'connection.logging.enabled');
    }

    public function processResponse(RequestTypes $requestType, ElasticsearchResponse $response): void
    {
        if (empty($response['errors']) && empty($response['failures'])) {
            return;
        }

        $exception = match ($requestType) {
            RequestTypes::BULK_SAVE => BulkSaveFail::makeFromResponse($response),
            RequestTypes::SAVE => SaveFail::makeFromResponse($response),

            default => throw new BaseException("Invalid request type \"{$requestType->value}\""),
        };

        $this->writeToLog($exception);
        $this->assertError($exception);
    }

    protected function writeToLog(OperationFail $exception): void
    {
        if ($this->isErrorsHandlingWriteToLog && $this->isLoggingEnabled) {
            $this->logger->error(
                $exception->getMessage(),
                $exception->context()
            );
        }
    }

    protected function assertError(OperationFail $exception): void
    {
        if ($this->isErrorsHandlingDebugEnabled) {
            throw $exception;
        }

        if (
            $this->isErrorsHandlingUseSentry
            && app()->bound('sentry')
        ) {
            app('sentry')->captureException($exception);
        }
    }
}
