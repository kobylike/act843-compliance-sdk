<?php

namespace GhanaCompliance\Act843SDK\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use GhanaCompliance\Act843SDK\Models\IpReputation;
use GhanaCompliance\Act843SDK\Services\Security\AlertService;

class DetectSuspiciousIP
{
    protected AlertService $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();

        // Check reputation – only log high risk, no blocking
        $reputation = IpReputation::where('ip', $ip)->first();
        if ($reputation && $reputation->score >= 70) {
            // Log to security channel
            logger("HIGH_RISK_IP_ACCESS", [
                'ip' => $ip,
                'score' => $reputation->score,
                'path' => $request->path(),
                'method' => $request->method(),
            ]);

            // Optionally send an alert (notification only)
            if ($reputation->score >= 90) {
                $this->alertService->send(
                    'HIGH',
                    'High Risk IP Access',
                    "IP {$ip} with score {$reputation->score} accessed {$request->path()}",
                    ['ip' => $ip, 'score' => $reputation->score, 'path' => $request->path()]
                );
            }
        }

        // ❌ NEVER block or return 403
        return $next($request);
    }
}
