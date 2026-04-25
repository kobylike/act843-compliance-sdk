<?php
// app/Compliance/SDK/EventTracker.php

namespace GhanaCompliance\Act843SDK\Compliance\SDK;

use GhanaCompliance\Act843SDK\Services\ComplianceService;

class EventTracker
{
    public function track(string $event, array $data): void
    {
        if ($event === 'auth.failed') {
            app(ComplianceService::class)->process([
                'ip' => $data['ip'],
                'attempts' => $data['attempts'],
                'type' => $data['type'] ?? 'EXTERNAL_FAILED_LOGIN',
            ]);
        }
        // Extend for other events
    }
}
