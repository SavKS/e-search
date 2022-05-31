<?php

use Monolog\Logger;
use Savks\ESearch\Debug\ClockworkPerformanceTracker;

return [
    'default_connection' => env('E_SEARCH_DEFAULT_CONNECTION', 'default'),

    'resources' => [],

    'performance_tracker' => ClockworkPerformanceTracker::class,

    'connections' => [
        'default' => [
            /**
             * Index prefix
             */
            'index_prefix' => env('E_SEARCH_INDEX_PREFIX', env('APP_NAME')),

            /**
             * Show performance events in clockwork
             */
            'enable_track_performance' => (bool)env(
                'E_SEARCH_TRACK_PERFORMANCE_ENABLE',
                env('APP_DEBUG')
            ),

            /*
             * Connection settings
             */
            'connection' => [

                /*
                |--------------------------------------------------------------------------
                | Hosts
                |--------------------------------------------------------------------------
                |
                | The most common configuration is telling the client about your cluster: how many nodes, their addresses and ports.
                | If no hosts are specified, the client will attempt to connect to localhost:9200.
                |
                */
                'hosts' => [
                    env('E_SEARCH_HOST', '127.0.0.1:9200'),
                ],

                /*
                |--------------------------------------------------------------------------
                | Reties
                |--------------------------------------------------------------------------
                |
                | By default, the client will retry n times, where n = number of nodes in your cluster.
                | A retry is only performed if the operation results in a "hard" exception.
                |
                */
                'retries' => env('E_SEARCH_RETRIES', 3),

                /*
                |------------------------------------------------------------------
                | Logging
                |------------------------------------------------------------------
                |
                | Logging is disabled by default for performance reasons. The recommended logger is Monolog (used by Laravel),
                | but any logger that implements the PSR/Log interface will work.
                |
                | @more https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#enabling_logger
                |
                */
                'logging' => [
                    'enabled' => env('E_SEARCH_LOG', true),
                    'path' => storage_path(env('E_SEARCH_LOG_PATH', 'logs/elasticsearch.log')),
                    'level' => env('E_SEARCH_LOG_LEVEL', Logger::WARNING),
                ],

                /*
                |------------------------------------------------------------------
                | Errors handling
                |------------------------------------------------------------------
                |
                */
                'errors_handling' => [
                    'debug_enabled' => env(
                        'E_SEARCH_ERRORS_HANDLING_DEBUG_ENABLE',
                        env('APP_DEBUG')
                    ),
                    'write_to_log' => env('E_SEARCH_ERRORS_HANDLING_WRITE_TO_LOG', true),
                    'use_sentry' => env('E_SEARCH_ERRORS_HANDLING_USE_SENTRY', false),
                ],
            ],
        ],
    ],
];
