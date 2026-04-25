<?php
// app/Compliance/SDK/ComplianceClient.php

namespace GhanaCompliance\Act843SDK\Compliance\SDK;

use GhanaCompliance\Act843SDK\Compliance\SDK\EventTracker;
use GhanaCompliance\Act843SDK\Compliance\SDK\AnalyzerProxy;

class ComplianceClient
{
    protected EventTracker $tracker;
    protected AnalyzerProxy $analyzer;

    public function __construct()
    {
        $this->tracker = new EventTracker();
        $this->analyzer = new AnalyzerProxy();
    }

    public function trackFailedLogin(string $ip, int $attempts): void
    {
        $this->tracker->track('auth.failed', [
            'ip' => $ip,
            'attempts' => $attempts,
            'type' => 'LOGIN_FAILURE',
        ]);
    }

    public function trackUnauthorizedAccess(string $ip, string $route): void
    {
        $this->tracker->track('auth.unauthorized', [
            'ip' => $ip,
            'route' => $route,
        ]);
    }

    public function analyzeRisk(string $ip, int $attempts): array
    {
        return $this->analyzer->analyze($ip, $attempts);
    }

    public function getReputation(string $ip): array
    {
        return $this->analyzer->getReputation($ip);
    }
}
