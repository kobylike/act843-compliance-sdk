<?php

namespace GhanaCompliance\Act843SDK\Services;

use GhanaCompliance\Act843SDK\Services\Security\ComplianceAnalyzer;
use GhanaCompliance\Act843SDK\Services\Security\ComplianceEngine;
use GhanaCompliance\Act843SDK\Services\Security\IpReputationEngine;
use GhanaCompliance\Act843SDK\Services\Security\AlertService;
use GhanaCompliance\Act843SDK\Services\Security\AutomatedResponse;
use GhanaCompliance\Act843SDK\Services\PatternDetectionService;
use GhanaCompliance\Act843SDK\Services\RecommendationEngine;
use GhanaCompliance\Act843SDK\Services\Security\AttackGraphPredictor;

class ComplianceService
{
    protected ComplianceAnalyzer $analyzer;
    protected IpReputationEngine $reputationEngine;
    protected AlertService $alertService;
    protected AutomatedResponse $automatedResponse;
    protected PatternDetectionService $patternDetector;

    public function __construct()
    {
        $this->analyzer = new ComplianceAnalyzer();
        $this->reputationEngine = app(IpReputationEngine::class);
        $this->alertService = app(AlertService::class);
        $this->automatedResponse = app(AutomatedResponse::class);
        $this->patternDetector = app(PatternDetectionService::class);
    }

    public function process(array $data): void
    {
        $ip = $data['ip'];
        $attempts = $data['attempts'] ?? 0;

        // REMOVED: The threshold check and logging – this is already done in LogFailedLoginAttempt
        // The listener already logged the event. Here we only do secondary actions.

        // 1. Analyze risk (for reputation and alerts, not for logging)
        $analysis = $this->analyzer->analyze([
            'type' => $data['type'] ?? 'BRUTE_FORCE',
            'attempts' => $attempts,
            'ip' => $ip,
        ]);

        // 2. Record transition for attack graph
        app(AttackGraphPredictor::class)->recordTransition(
            $ip,
            $analysis['type'],
            $data['route'] ?? request()->path()
        );

        // 3. Detect patterns (for alert context only)
        $patterns = $this->patternDetector->detect($data);

        // 4. Update IP reputation (always do this)
        $reputation = $this->reputationEngine->update($ip, $attempts, $analysis['score']);

        // 5. Alert for high severity (only if severity is HIGH)
        if ($analysis['severity'] === 'HIGH') {
            $this->alertService->send(
                'HIGH',
                'High Severity Security Event',
                "IP {$ip} triggered high severity with score {$analysis['score']} after {$attempts} attempts",
                ['analysis' => $analysis, 'reputation' => $reputation->toArray()]
            );
        }

        // 6. Automated response (suggestions only)
        $analysis['ip'] = $ip;
        $response = $this->automatedResponse->handle($analysis);

        if (!empty($response['recommendations'])) {
            logger("Recommendations for {$ip}: " . json_encode($response['recommendations']));
        }
    }
}
