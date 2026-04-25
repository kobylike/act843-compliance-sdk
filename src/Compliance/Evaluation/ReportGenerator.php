<?php
// app/Compliance/Evaluation/ReportGenerator.php

namespace GhanaCompliance\Act843SDK\Compliance\Evaluation;

use GhanaCompliance\Act843SDK\Models\ComplianceLog;
use Carbon\Carbon;
use PDF; // if installed, else use array/JSON

class ReportGenerator
{
    public function generate(array $metrics, array $simulations): array
    {
        $report = [
            'generated_at' => now()->toIso8601String(),
            'framework' => 'Privacy-Preserving AI Compliance Monitor',
            'dsr_metrics' => $metrics,
            'attack_simulation_results' => $simulations,
            'compliance_score' => $this->calculateComplianceScore($metrics),
            'recommendations' => $this->generateRecommendations($metrics, $simulations),
        ];

        // Store report as JSON in storage
        $path = storage_path('app/compliance_reports/report_' . now()->format('Ymd_His') . '.json');
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT));

        return $report;
    }

    protected function calculateComplianceScore(array $metrics): int
    {
        $score = 100;
        if ($metrics['false_positives'] > 0) $score -= min(30, $metrics['false_positives'] * 5);
        if ($metrics['total_events'] > 0 && $metrics['true_positives'] == 0) $score -= 20;
        if ($metrics['overhead_percent'] > 10) $score -= 10;
        return max(0, $score);
    }

    protected function generateRecommendations(array $metrics, array $simulations): array
    {
        $recs = [];
        if ($metrics['false_positives'] > 5) {
            $recs[] = 'Adjust scoring thresholds to reduce false positives.';
        }
        if ($metrics['overhead_percent'] > 10) {
            $recs[] = 'Optimize query performance or implement caching.';
        }
        return $recs;
    }
}
