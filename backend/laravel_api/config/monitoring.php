<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Health Check Alerting
    |--------------------------------------------------------------------------
    |
    | Configure how health check alerts are sent and what thresholds trigger
    | notifications. The health:monitor command runs on a schedule and
    | tracks consecutive failures to prevent alert fatigue.
    |
    */

    'health_check' => [

        /*
        |--------------------------------------------------------------------------
        | Alert Channels
        |--------------------------------------------------------------------------
        |
        | Which notification channels to use for health check alerts.
        | Supported: 'slack', 'mail', 'log'
        |
        */

        'alert_channels' => [
            'slack' => env('HEALTH_ALERT_SLACK_ENABLED', false),
            'mail' => env('HEALTH_ALERT_MAIL_ENABLED', false),
        ],

        /*
        |--------------------------------------------------------------------------
        | Alert Thresholds
        |--------------------------------------------------------------------------
        |
        | - failure_threshold: Jumlah kegagalan berurutan sebelum alert dikirim
        | - recovery_threshold: Jumlah keberhasilan berurutan untuk mengirim "recovered"
        | - check_interval: Interval pengecekan dalam menit (biasanya diatur di schedule)
        |
        */

        'failure_threshold' => env('HEALTH_ALERT_FAILURE_THRESHOLD', 3),
        'recovery_threshold' => env('HEALTH_ALERT_RECOVERY_THRESHOLD', 2),

        /*
        |--------------------------------------------------------------------------
        | Latency Thresholds (ms)
        |--------------------------------------------------------------------------
        |
        | Jika latency melebihi threshold ini, akan tercatat sebagai warning
        | dalam log dan ditambahkan ke detail notifikasi.
        |
        */

        'latency_warning' => env('HEALTH_ALERT_LATENCY_WARNING', 500),
        'latency_critical' => env('HEALTH_ALERT_LATENCY_CRITICAL', 1000),

        /*
        |--------------------------------------------------------------------------
        | Recipients for Mail Alerts
        |--------------------------------------------------------------------------
        |
        | Email address(es) that will receive health check alert notifications.
        | Supports comma-separated list for multiple recipients.
        |
        */

        'mail_recipients' => array_filter(explode(',', (string) env('HEALTH_ALERT_MAIL_RECIPIENTS', ''))),

    ],

];
