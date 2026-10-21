<?php

return [
    'timezone' => env('WATCHPOST_TIMEZONE', 'Europe/Amsterdam'),
    'digest_time' => env('WATCHPOST_DIGEST_TIME', '07:00'),
    'mail_enabled' => (bool) env('WATCHPOST_MAIL_ENABLED', false),
    'recipients' => array_values(array_filter(array_map('trim', explode(',', env('WATCHPOST_DIGEST_RECIPIENTS', ''))))),
    'severity' => [
        'auth.login_failed' => 3, 'auth.lockout' => 2, 'auth.login_success' => 5,
        'auth.password_changed' => 4, 'auth.token_invalid' => 3, 'probe.404' => 4,
        'probe.scan' => 2, 'access.admin' => 4, 'account.created' => 4,
        'account.suspended' => 3, 'data.export' => 2, 'system.error' => 3,
    ],
];
