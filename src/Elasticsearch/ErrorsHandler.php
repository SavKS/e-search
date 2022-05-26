<?php

namespace Savks\ESearch\Elasticsearch;

use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;
use Illuminate\Foundation\Application;

use Savks\ESearch\Exceptions\{
    BaseException,
    BulkSaveFail,
    OperationFail,
    SaveFail
};
use Savks\ESearch\Elasticsearch\RequestTypes;

class ErrorsHandler
{
    /**
     * @var Application
     */
    protected Application $app;

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
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $config = $this->app['config'];

        $this->isErrorsHandlingDebugEnabled = (bool)$config->get('e-search.connection.errors_handling.debug_enabled');
        $this->isErrorsHandlingWriteToLog = (bool)$config->get('e-search.connection.errors_handling.write_to_log');
        $this->isErrorsHandlingUseSentry = (bool)$config->get('e-search.connection.errors_handling.use_sentry');

        $this->isLoggingEnabled = (bool)$config->get('e-search.connection.logging.enabled');
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
            app('e-search.logger')->error(
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
        if (\config('e-search.connection.errors_handling.debug_enabled')) {
            throw $exception;
        }

        if ($this->isErrorsHandlingUseSentry
            && app()->bound('sentry')
        ) {
            app('sentry')->captureException($exception);
        }
    }
}
