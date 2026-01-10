<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sentry DSN
    |--------------------------------------------------------------------------
    |
    | The DSN tells the SDK where to send the events to. You can find your
    | project's DSN in the "Client Keys" section of your project settings.
    |
    */

    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    /*
    |--------------------------------------------------------------------------
    | Sentry Release
    |--------------------------------------------------------------------------
    |
    | This is used to identify the release version of your application in Sentry.
    |
    */

    'release' => env('SENTRY_RELEASE'),

    /*
    |--------------------------------------------------------------------------
    | Sentry Environment
    |--------------------------------------------------------------------------
    |
    | Set the environment name (production, staging, etc.)
    |
    */

    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),

    /*
    |--------------------------------------------------------------------------
    | Sample Rate
    |--------------------------------------------------------------------------
    |
    | Configure the percentage of events to send to Sentry (0.0 to 1.0)
    | For production, use 0.2 (20%) to reduce costs
    |
    */

    'sample_rate' => (float) env('SENTRY_SAMPLE_RATE', 1.0),

    /*
    |--------------------------------------------------------------------------
    | Traces Sample Rate
    |--------------------------------------------------------------------------
    |
    | Configure the percentage of transactions to send (for performance monitoring)
    |
    */

    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.2),

    /*
    |--------------------------------------------------------------------------
    | Send Default PII
    |--------------------------------------------------------------------------
    |
    | If this option is enabled, certain personally identifiable information (PII)
    | is added by active integrations. Without this flag they are never added.
    |
    */

    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),

    /*
    |--------------------------------------------------------------------------
    | Breadcrumbs
    |--------------------------------------------------------------------------
    |
    | Breadcrumbs are a trail of events that happened prior to an issue.
    |
    */

    'breadcrumbs' => [
        // Capture Laravel logs as breadcrumbs
        'logs' => env('SENTRY_BREADCRUMBS_LOGS', true),

        // Capture Laravel cache events
        'cache' => env('SENTRY_BREADCRUMBS_CACHE', true),

        // Capture Livewire components
        'livewire' => env('SENTRY_BREADCRUMBS_LIVEWIRE', false),

        // Capture SQL queries
        'sql_queries' => env('SENTRY_BREADCRUMBS_SQL_QUERIES', true),

        // Capture bindings on SQL queries
        'sql_bindings' => env('SENTRY_BREADCRUMBS_SQL_BINDINGS', false),

        // Capture queue job information
        'queue_info' => env('SENTRY_BREADCRUMBS_QUEUE_INFO', true),

        // Capture command information
        'command_info' => env('SENTRY_BREADCRUMBS_COMMAND_INFO', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integrations
    |--------------------------------------------------------------------------
    |
    | Configure which Sentry integrations should be loaded.
    |
    */

    'integrations' => [
        // Enable ignoring specific errors
        'ignore_errors' => [
            // Laravel's not found exception
            Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,

            // Token mismatch
            Illuminate\Session\TokenMismatchException::class,

            // Validation exceptions
            Illuminate\Validation\ValidationException::class,
        ],

        // Ignore specific transactions
        'ignore_transactions' => [
            // Health check endpoints
            'GET /api/health',
            'GET /api/health/detailed',
            'GET /up',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Enable tracing to track performance
    |
    */

    'tracing' => [
        // Trace SQL queries
        'sql_queries' => env('SENTRY_TRACE_SQL_QUERIES', true),

        // Trace queue jobs
        'queue_jobs' => env('SENTRY_TRACE_QUEUE_JOBS', true),

        // Trace HTTP client requests
        'http_client_requests' => env('SENTRY_TRACE_HTTP_CLIENT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Context
    |--------------------------------------------------------------------------
    |
    | Additional context to include with every event
    |
    */

    'context' => [
        'user_context' => true,
        'request_context' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Before Send
    |--------------------------------------------------------------------------
    |
    | This option allows you to modify or drop events before they are sent.
    |
    */

    'before_send' => function (\Sentry\Event $event): ?\Sentry\Event {
        // Don't send events in local environment
        if (app()->environment('local')) {
            return null;
        }

        return $event;
    },

];
