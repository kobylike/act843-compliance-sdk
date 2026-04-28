<?php

namespace GhanaCompliance\Act843SDK\Services;

use GhanaCompliance\Act843SDK\Services\Security\ComplianceAnalyzer;
use GhanaCompliance\Act843SDK\Services\Security\ComplianceEngine;
use GhanaCompliance\Act843SDK\Services\Security\IpReputationEngine;
use GhanaCompliance\Act843SDK\Services\Security\AlertService;
use GhanaCompliance\Act843SDK\Services\Security\AutomatedResponse;
use GhanaCompliance\Act843SDK\Services\PatternDetectionService;
use GhanaCompliance\Act843SDK\Services\RecommendationEngine;
use GhanaCompliance\Act843SDK\Services\Security\AttackGraphPredictor;   // ✅ IMPORT

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

        // Log only at important thresholds
        $thresholds = config('security.log_thresholds', [1, 3, 5, 10, 15, 20, 25, 30, 40, 50]);
        if (!in_array($attempts, $thresholds) && $attempts <= 50) {
            return;
        }

        // 1. Analyze risk (produces $analysis)
        $analysis = $this->analyzer->analyze([
            'type' => $data['type'] ?? 'BRUTE_FORCE',
            'attempts' => $attempts,
            'ip' => $ip,
        ]);

        // 2. Record transition for attack graph (after analysis is ready)
        app(AttackGraphPredictor::class)->recordTransition(
            $ip,
            $analysis['type'],
            $data['route'] ?? request()->path()
        );

        // 3. Detect patterns
        $patterns = $this->patternDetector->detect($data);
        if (!empty($patterns)) {
            $analysis['patterns'] = $patterns;
        }

        // 4. Generate recommendation
        $recommendation = app(RecommendationEngine::class)->generate($analysis);

        // 5. Log to compliance
        ComplianceEngine::log([
            ...$analysis,
            'attempts' => $attempts,
            'meta' => [
                ...$data,
                'analysis_details' => $analysis['analysis'] ?? [],
                'patterns' => $patterns,
            ],
            'recommendation' => $recommendation['action'],
        ]);

        // 6. Update IP reputation
        $reputation = $this->reputationEngine->update($ip, $attempts, $analysis['score']);

        // 7. Alert for high severity
        if ($analysis['severity'] === 'HIGH') {
            $this->alertService->send(
                'HIGH',
                'High Severity Security Event',
                "IP {$ip} triggered high severity with score {$analysis['score']} after {$attempts} attempts",
                ['analysis' => $analysis, 'reputation' => $reputation->toArray()]
            );
        }

        // 8. Automated response (suggestions only)
        $analysis['ip'] = $ip;
        $response = $this->automatedResponse->handle($analysis);

        if (!empty($response['recommendations'])) {
            logger("Recommendations for {$ip}: " . json_encode($response['recommendations']));
        }
    }
}
