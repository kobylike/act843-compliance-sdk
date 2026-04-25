<?php
// app/Compliance/Evaluation/MetricsCollector.php

namespace GhanaCompliance\Act843SDK\Compliance\Evaluation;

use GhanaCompliance\Act843SDK\Models\ComplianceLog;
use Carbon\Carbon;

class MetricsCollector
{
    protected array $metrics = [];

    public function collect(Carbon $from, Carbon $to): array
    {
        $logs = ComplianceLog::whereBetween('created_at', [$from, $to])->get();

        $this->metrics = [
            'period' => ['from' => $from, 'to' => $to],
            'total_events' => $logs->count(),
            'true_positives' => 0,    // to be filled by attack simulation
            'false_positives' => 0,
            'latency_avg_ms' => $this->calculateAvgLatency($logs),
            'overhead_percent' => $this->calculateOverhead(),
            'score_distribution' => [
                'low' => $logs->where('severity', 'LOW')->count(),
                'medium' => $logs->where('severity', 'MEDIUM')->count(),
                'high' => $logs->where('severity', 'HIGH')->count(),
            ],
        ];

        return $this->metrics;
    }

    protected function calculateAvgLatency($logs): float
    {
        // Assuming logs have meta.latency_ms (if we store it)
        $latencies = $logs->pluck('meta.latency_ms')->filter()->toArray();
        return empty($latencies) ? 0 : array_sum($latencies) / count($latencies);
    }

    protected function calculateOverhead(): float
    {
        // Simulated overhead (in real scenario, compare request time with/without compliance)
        return 5.2; // example 5.2% overhead
    }

    public function setTruePositives(int $count): void
    {
        $this->metrics['true_positives'] = $count;
    }

    public function setFalsePositives(int $count): void
    {
        $this->metrics['false_positives'] = $count;
    }
}
