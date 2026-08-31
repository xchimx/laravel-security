<?php

return [
    'audit' => [
        'subject' => '🔒 Security Vulnerabilities Detected - :app',
        'greeting' => 'Security Alert for :app',
        'summary' => 'We have detected **:count security vulnerabilities** in your application dependencies.',
        'source_summary' => '**:source**: :count vulnerabilities found',
        'source_summary_new' => '**:source**: :count new vulnerabilities found',
        'still_open' => ':count known vulnerabilities are still open.',
        'more' => '... and :count more vulnerabilities',
        'action' => 'View Full Report',
        'footer' => 'Please review and update the affected packages as soon as possible.',
        'slack_text' => '🔒 Security vulnerabilities detected in :app',
        'slack_header' => '🔒 Security Alert - :app',
        'slack_summary' => 'We have detected *:count security vulnerabilities* in your application dependencies.',
        'slack_source_summary' => '*:source*: :count vulnerabilities found',
        'slack_source_summary_new' => '*:source*: :count new vulnerabilities found',
    ],

    'outdated' => [
        'subject' => '📦 Outdated Packages Report - :app',
        'greeting' => 'Package Update Report for :app',
        'summary' => 'We found **:count outdated packages** in your application dependencies.',
        'source_summary' => '**:source**: :count outdated packages',
        'more' => '... and :count more packages',
        'action' => 'View Full Report',
        'footer' => 'Consider updating these packages to their latest versions.',
        'slack_text' => '📦 Outdated packages detected in :app',
        'slack_header' => '📦 Outdated Packages Report - :app',
        'slack_summary' => 'We found *:count outdated packages* in your application dependencies.',
        'slack_source_summary' => '*:source*: :count outdated packages',
    ],

    'view_dashboard' => 'View Full Dashboard',
];
