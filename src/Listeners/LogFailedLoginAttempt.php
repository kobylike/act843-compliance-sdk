<?php

namespace GhanaCompliance\Act843SDK\Listeners;

use Illuminate\Auth\Events\Failed;
use GhanaCompliance\Act843SDK\Services\ComplianceService;
use Illuminate\Support\Facades\Cache;

class LogFailedLoginAttempt
{
    public function handle(Failed $event): void
    {
        $ip = request()->ip();

        // 1. Failed attempt counter
        $attempts = Cache::get("failures_$ip", 0) + 1;
        Cache::put("failures_$ip", $attempts, now()->addMinutes(10));

        // 2. Username tracking – hashed with APP_KEY (privacy preserving)
        $rawUsername = $event->credentials['email'] ?? request()->input('email') ?? 'unknown';
        $hashedUsername = hash_hmac('sha256', $rawUsername, config('app.key'));

        $userKey = "usernames_{$ip}";
        $usernames = Cache::get($userKey, []);
        $usernames[$hashedUsername] = true; // use associative to auto‑deduplicate
        Cache::put($userKey, $usernames, now()->addMinutes(10));

        // 3. Determine attack type
        $type = match (true) {
            $attempts >= 10 => 'AGGRESSIVE_BRUTE_FORCE',
            $attempts >= 5  => 'BRUTE_FORCE',
            $attempts >= 3  => 'REPEATED_FAILED_LOGIN',
            default         => 'FAILED_LOGIN',
        };

        // 4. Send to ComplianceService (no raw usernames)
        app(ComplianceService::class)->process([
            'ip' => $ip,
            'attempts' => $attempts,
            'type' => $type,
            'unique_usernames_count' => count($usernames), // only count, not the usernames
            'user_agent' => request()->userAgent(),
            'route' => request()->path(),
        ]);
    }
}
