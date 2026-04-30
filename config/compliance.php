<?php

return [
    'retention' => [
        'compliance_logs' => env('COMPLIANCE_RETENTION_DAYS', 90),
        'security_events' => env('SECURITY_EVENTS_RETENTION_DAYS', 30),
        'ip_reputations' => env('IP_REPUTATION_RETENTION_DAYS', 180),
    ],
    'password' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'complexity' => env('PASSWORD_COMPLEXITY', true),
    ],
    'evaluation' => [
        'simulation_ips' => ['127.0.0.100', '127.0.0.101'],
    ],

    // 🔥 NEW: Allow deep password scan (sampling user hashes)
    'allow_deep_password_scan' => env('ALLOW_DEEP_PASSWORD_SCAN', false),
    'report_email' => env('COMPLIANCE_REPORT_EMAIL', 'kobylike2@gmail.com'),
    'anomaly_detection' => env('COMPLIANCE_ANOMALY_DETECTION', false),
    'privilege_escalation_detection' => env('COMPLIANCE_PRIVILEGE_DETECTION', true),
    'proactive_password_check' => env('COMPLIANCE_PROACTIVE_PASSWORD_CHECK', true),
    'regulator_api_url' => env('COMPLIANCE_REGULATOR_API_URL'),
    'regulator_api_key' => env('COMPLIANCE_REGULATOR_API_KEY'),
];
