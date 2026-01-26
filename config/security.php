<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Audit Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the security audit settings for composer and npm.
    |
    */

    'audit' => [
        'enabled' => env('SECURITY_AUDIT_ENABLED', true),
        'time' => env('SECURITY_AUDIT_TIME', '02:00'),
        'composer' => env('SECURITY_AUDIT_COMPOSER', true),
        'npm' => env('SECURITY_AUDIT_NPM', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Outdated Packages Check Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the outdated packages check settings.
    |
    */

    'outdated' => [
        'enabled' => env('SECURITY_OUTDATED_ENABLED', true),
        'time' => env('SECURITY_OUTDATED_TIME', '03:00'),
        'composer' => env('SECURITY_OUTDATED_COMPOSER', true),
        'npm' => env('SECURITY_OUTDATED_NPM', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which notification channels to use and their settings.
    |
    */

    'notifications' => [
        'user_model' => env('SECURITY_NOTIFICATIONS_USER_MODEL', 'App\Models\User'),
        'user_id' => env('SECURITY_NOTIFY_USER_ID', 1),
        'route' => env('SECURITY_NOTIFICATIONS_ROUTE', 'admin.security'),
        'channels' => [
            'mail' => env('SECURITY_NOTIFY_MAIL', true),
            'database' => env('SECURITY_NOTIFY_DATABASE', true),
            'slack' => env('SECURITY_NOTIFY_SLACK', false),
        ],

        // List of email addresses to notify
        'mail_to' => env('SECURITY_MAIL_TO', 'admin@example.com'),
    ],
];
