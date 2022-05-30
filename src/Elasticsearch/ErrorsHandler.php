<?php

namespace Savks\ESearch\Elasticsearch;

use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;
use Illuminate\Support\Arr;
use Monolog\Logger;

use Savks\ESearch\Exceptions\{
    BaseException,
    BulkSaveFail,
    OperationFail,
    SaveFail
};

class ErrorsHandler
{
    /**
     * @var array
     */
    protected array $config;

    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * @var bool
     */
    protected bool $isErrorsHandlingDebugEnabled;

    /**
     * @var bool
     */
    protected bool $isErrorsHandlingWriteToLog;

    /**
     * @var bool
     */
    protected bool $isErrorsHandlingUseSentry;

    /**
     * @var bool
     */
    protected bool $isLoggingEnabled;

    /**
     * @param array $config
     * @param Logger $logger
     */
    public function __construct(array $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;

        $this->isErrorsHandlingDebugEnabled = (bool)Arr::get(
            $config,
            'e-search.connection.errors_handling.debug_enabled'
        );
        $this->isErrorsHandlingWriteToLog = (bool)Arr::get(
            $config,
            'e-search.connection.errors_handling.write_to_log'
        );
        $this->isErrorsHandlingUseSentry = (bool)Arr::get(
            $config,
            'e-search.connection.errors_handling.use_sentry'
        );

        $this->isLoggingEnabled = (bool)Arr::get(
            $config,
            'e-search.connection.logging.enabled'
        );
    }

    /**
     * @param RequestTypes $requestType
     * @param ElasticsearchResponse $response
     * @return void
     */
    public function processResponse(RequestTypes $requestType, ElasticsearchResponse $response): void
    {
        if (! $response['errors']) {
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

    /**
     * @param OperationFail $exception
     * @return void
     */
    protected function writeToLog(OperationFail $exception): void
    {
        if ($this->isErrorsHandlingWriteToLog && $this->isLoggingEnabled) {
            $this->logger->error(
                $exception->getMessage(),
                $exception->context()
            );
        }
    }

    /**
     * @param OperationFail $exception
     * @return void
     */
    protected function assertError(OperationFail $exception): void
    {
        if ($this->isErrorsHandlingDebugEnabled) {
            throw $exception;
        }

        if ($this->isErrorsHandlingUseSentry
            && app()->bound('sentry')
        ) {
            app('sentry')->captureException($exception);
        }
    }
}
