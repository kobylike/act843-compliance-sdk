<?php

namespace GhanaCompliance\Act843SDK\Services;

use GhanaCompliance\Act843SDK\Models\ComplianceLog;

class ComplianceHealthService
{
    public function getHealthMetrics(): array
    {
        $passwordScan = ComplianceLog::where('type', 'PASSWORD_POLICY_SCAN')->latest()->first();
        $retentionScan = ComplianceLog::where('type', 'DATA_RETENTION_SCAN')->latest()->first();

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

    protected function calculateComplianceScore($passwordScan, $retentionScan): int
    {
        $score = 100;

        if ($passwordScan) {
            $meta = $passwordScan->meta;
            if (($meta['weak_hashes'] ?? 0) > 0) $score -= 30;
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
        return [
            'status' => ($meta['weak_hashes'] ?? 0) > 0 || ($meta['weak_policies'] ?? 0) > 0 ? '⚠️ Issues found' : '✅ Compliant',
            'weak_hashes' => $meta['weak_hashes'] ?? 0,
            'weak_policies' => $meta['weak_policies'] ?? 0,
        ];
    }

    protected function extractRetentionMetrics($scan): array
    {
        if (!$scan) return ['status' => 'No scan yet', 'non_compliant' => 0];
        $meta = $scan->meta;
        return [
            'status' => ($meta['non_compliant_tables'] ?? 0) > 0 ? '⚠️ Non‑compliant tables' : '✅ Compliant',
            'non_compliant' => $meta['non_compliant_tables'] ?? 0,
        ];
    }

    protected function compileRecommendations($passwordScan, $retentionScan): array
    {
        $recs = [];
        if ($passwordScan && ($passwordScan->meta['weak_policies'] ?? 0) > 0) {
            $recs[] = 'Enforce password minimum length (≥8) and complexity.';
        }
        if ($passwordScan && ($passwordScan->meta['weak_hashes'] ?? 0) > 0) {
            $recs[] = 'Re‑hash passwords using Bcrypt or Argon2.';
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
