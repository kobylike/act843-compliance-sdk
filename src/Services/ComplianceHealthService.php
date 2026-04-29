<?php

namespace GhanaCompliance\Act843SDK\Services;

use GhanaCompliance\Act843SDK\Models\ComplianceLog;
use Carbon\Carbon;

class ComplianceHealthService
{
    public function getHealthMetrics(): array
    {
        // Get the most severe password policy scan (highest score) in the last 24 hours,
        // fallback to the latest scan overall.
        $passwordScan = $this->getMostSevereScan('PASSWORD_POLICY_SCAN', 24);
        $retentionScan = $this->getMostSevereScan('DATA_RETENTION_SCAN', 24);

        $score = $this->calculateComplianceScore($passwordScan, $retentionScan);

        return [
            'score' => $score,
            'grade' => $this->getGrade($score),
            'password_policy' => $this->extractPasswordMetrics($passwordScan),
            'data_retention' => $this->extractRetentionMetrics($retentionScan),
            'last_checks' => [
                'password' => $passwordScan?->created_at,
                'retention' => $retentionScan?->created_at,
            ],
            'recommendations' => $this->compileRecommendations($passwordScan, $retentionScan),
        ];
    }

    /**
     * Get the most severe scan (highest score) of a given type within the last N hours.
     * If none, return the latest scan overall.
     */
    protected function getMostSevereScan(string $type, int $hours = 24)
    {
        $cutoff = Carbon::now()->subHours($hours);
        $scan = ComplianceLog::where('type', $type)
            ->where('created_at', '>=', $cutoff)
            ->orderBy('score', 'desc')
            ->first();

        if (!$scan) {
            // Fallback to the latest scan overall (any time)
            $scan = ComplianceLog::where('type', $type)
                ->orderBy('created_at', 'desc')
                ->first();
        }
        return $scan;
    }

    protected function calculateComplianceScore($passwordScan, $retentionScan): int
    {
        $score = 100;

        if ($passwordScan) {
            $meta = $passwordScan->meta;
            if (($meta['weak_hashes_count'] ?? 0) > 0) $score -= 30;
            if (($meta['weak_policies'] ?? 0) > 0) $score -= 20 * ($meta['weak_policies']);
        } else {
            $score -= 20;
        }

        if ($retentionScan && ($retentionScan->meta['non_compliant_tables'] ?? 0) > 0) {
            $score -= 10 * $retentionScan->meta['non_compliant_tables'];
        }

        return max(0, min(100, $score));
    }

    protected function getGrade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F',
        };
    }

    protected function extractPasswordMetrics($scan): array
    {
        if (!$scan) return ['status' => 'No scan yet', 'weak_hashes' => 0, 'weak_policies' => 0];
        $meta = $scan->meta;
        $weakHashes = $meta['weak_hashes_count'] ?? 0;
        $weakPolicies = $meta['weak_policies'] ?? 0;
        $status = ($weakHashes > 0 || $weakPolicies > 0) ? '⚠️ Issues found' : '✅ Compliant';
        return [
            'status' => $status,
            'weak_hashes' => $weakHashes,
            'weak_policies' => $weakPolicies,
        ];
    }

    protected function extractRetentionMetrics($scan): array
    {
        if (!$scan) return ['status' => 'No scan yet', 'non_compliant' => 0];
        $meta = $scan->meta;
        $nonCompliant = $meta['non_compliant_tables'] ?? 0;
        $status = $nonCompliant > 0 ? '⚠️ Non‑compliant tables' : '✅ Compliant';
        return [
            'status' => $status,
            'non_compliant' => $nonCompliant,
        ];
    }

    protected function compileRecommendations($passwordScan, $retentionScan): array
    {
        $recs = [];
        if ($passwordScan) {
            $meta = $passwordScan->meta;
            if (($meta['weak_policies'] ?? 0) > 0) {
                $recs[] = 'Enforce password minimum length (≥8) and complexity.';
            }
            if (($meta['weak_hashes_count'] ?? 0) > 0) {
                $recs[] = 'Re‑hash passwords using Bcrypt or Argon2.';
            }
        }
        if ($retentionScan && ($retentionScan->meta['non_compliant_tables'] ?? 0) > 0) {
            $recs[] = 'Define and enforce retention policies for outdated data.';
        }
        if (empty($recs)) {
            $recs[] = 'All compliance checks passed – maintain current configuration.';
        }
        return $recs;
    }
}
