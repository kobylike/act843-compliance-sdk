<?php

namespace GhanaCompliance\Act843SDK\Compliance\Modules\AuthenticationSecurity;

use GhanaCompliance\Act843SDK\Models\ComplianceLog;
use GhanaCompliance\Act843SDK\Services\Security\AlertService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PasswordPolicyChecker
{
    /**
     * Analyze password policies.
     * Default mode: only check configuration (zero DB access).
     * Deep mode: sample a small percentage of users to detect weak hash patterns.
     */
    public function analyze(bool $deep = false): array
    {
        $stats = [
            'total_users' => 0,
            'weak_hashes' => 0,
            'weak_policies' => 0,
            'common_passwords' => 0,
            'recommendations' => [],
        ];

        // 1. Policy checks from compliance config (no DB)
        $minLength = config('compliance.password.min_length', 8);
        if ($minLength < 8) {
            $stats['weak_policies']++;
            $stats['recommendations'][] = 'Set password minimum length to 8 or more.';
        }

        $hasComplexity = config('compliance.password.complexity', true);
        if (!$hasComplexity) {
            $stats['weak_policies']++;
            $stats['recommendations'][] = 'Enforce password complexity (uppercase, lowercase, numbers, special chars).';
        }

        // 2. Hashing algorithm audit (config based, efficient)
        $driver = config('hashing.driver', 'bcrypt');
        if (!in_array($driver, ['bcrypt', 'argon2', 'argon2id'])) {
            $stats['weak_hashes']++;
            $stats['recommendations'][] = 'Switch hashing driver to bcrypt or Argon2.';
        }

        // 3. Optional deep scan (only if explicitly requested and allowed)
        if ($deep && config('compliance.allow_deep_password_scan', false)) {
            $this->runDeepScan($stats);
        }

        // 4. Send alert if new weak policy detected (once per day, to avoid spam)
        if ($stats['weak_policies'] > 0 && !$this->alertSentToday('password_policy_weak')) {
            app(AlertService::class)->send(
                'MEDIUM',
                'Password Policy Weak',
                'Your password policy does not meet minimum requirements. Check config/compliance.php',
                ['recommendations' => $stats['recommendations']]
            );
            $this->markAlertSent('password_policy_weak');
        }

        // Log once (privacy metadata only)
        ComplianceLog::create([
            'type' => 'PASSWORD_POLICY_SCAN',
            'ip_address' => 'system',
            'score' => $stats['weak_hashes'] > 0 || $stats['weak_policies'] > 0 ? 70 : 10,
            'severity' => $stats['weak_hashes'] > 0 ? 'HIGH' : 'LOW',
            'attempts' => 0,
            'meta' => [
                'weak_hashes_count' => $stats['weak_hashes'],
                'weak_policies' => $stats['weak_policies'],
                'total_users_scanned' => $stats['total_users'],
                'recommendations' => $stats['recommendations'],
            ],
            'recommendation' => implode('; ', $stats['recommendations']),
        ]);

        return $stats;
    }

    /**
     * Deep scan: sample a small random subset of users, never store raw hashes,
     * only classify weak/strong patterns.
     */
    protected function runDeepScan(array &$stats): void
    {
        $total = DB::table('users')->count();
        if ($total === 0) return;

        $stats['total_users'] = $total;

        // Sample 5% of users (min 100, max 10,000)
        $sampleSize = min(10000, max(100, (int)($total * 0.05)));

        $users = DB::table('users')
            ->inRandomOrder()
            ->limit($sampleSize)
            ->get(['password']);

        foreach ($users as $user) {
            if ($this->isWeakHash($user->password)) {
                $stats['weak_hashes']++;
            }
        }
    }

    /**
     * Determine if a password hash is weak.
     * Weak = MD5 (32 hex), SHA1 (40 hex), or any unknown format.
     * Strong = bcrypt ($2y$, $2a$, $2b$) or Argon2 ($argon2).
     */
    protected function isWeakHash(string $hash): bool
    {
        // Strong modern hashes
        if (preg_match('/^\$2[aby]\$\d+\$/', $hash)) return false;
        if (str_starts_with($hash, '$argon2')) return false;

        // Weak: unsalted MD5/SHA1 (32 or 40 hex characters)
        if (preg_match('/^[a-f0-9]{32}$/i', $hash)) return true;
        if (preg_match('/^[a-f0-9]{40}$/i', $hash)) return true;

        // Unknown format – treat as weak for safety
        return true;
    }

    /**
     * Check if an alert has already been sent today for a given key.
     */
    protected function alertSentToday(string $key): bool
    {
        return Cache::get("compliance_alert_{$key}", false);
    }

    /**
     * Mark that an alert has been sent today for a given key.
     */
    protected function markAlertSent(string $key): void
    {
        Cache::put("compliance_alert_{$key}", true, now()->addDay());
    }
}
