<?php

return [
    'retention' => [
        'compliance_logs' => env('COMPLIANCE_RETENTION_DAYS', 90),
        'security_events' => env('SECURITY_EVENTS_RETENTION_DAYS', 30),
        'ip_reputations' => env('IP_REPUTATION_RETENTION_DAYS', 180),
    ],
    'password' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 12),
        'complexity' => env('PASSWORD_COMPLEXITY', true),
    ],
    'allow_deep_password_scan' => env('ALLOW_DEEP_PASSWORD_SCAN', false),
    'evaluation' => [
        'simulation_ips' => ['127.0.0.100', '127.0.0.101'],
    ],
    'report_email' => env('COMPLIANCE_REPORT_EMAIL', 'admin@example.com'),
    'anomaly_detection' => env('COMPLIANCE_ANOMALY_DETECTION', false),
    'proactive_password_check' => env('COMPLIANCE_PROACTIVE_PASSWORD_CHECK', true),

    /*
    |--------------------------------------------------------------------------
    | Role-Based Access Monitoring
    |--------------------------------------------------------------------------
    */
    'rbac' => [
        // Patterns for routes to scan in the audit command
        'enforce_on_routes_containing' => [
            'dashboard',
            'admin',
            'compliance',
            'security',
        ],

        // Driver: 'spatie' (uses hasRole) or 'native' (uses column)
        'driver' => env('COMPLIANCE_RBAC_DRIVER', 'spatie'),

        // Column name for native driver
        'role_column' => env('COMPLIANCE_ROLE_COLUMN', 'role'),

        // Log unauthorized attempts without blocking? (false = block & log)
        'log_only' => env('COMPLIANCE_RBAC_LOG_ONLY', false),

        // Required role for protected routes (used by the middleware)
        'required_role' => env('COMPLIANCE_REQUIRED_ROLE', 'compliance'),

        // Score to assign when unauthorized access is detected
        'unauthorized_score' => 80,
    ],
];
