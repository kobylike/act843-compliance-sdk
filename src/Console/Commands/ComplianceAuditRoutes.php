<?php

namespace GhanaCompliance\Act843SDK\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use GhanaCompliance\Act843SDK\Services\Security\ComplianceEngine;

class ComplianceAuditRoutes extends Command
{
    protected $signature = 'compliance:audit-routes 
                            {--role=compliance : Required role to check}
                            {--log-missing : Log missing authorization as compliance event}
                            {--report-only : Only show report, no logging}';

    protected $description = 'Scan all routes and detect missing role-based protection';

    public function handle()
    {
        $requiredRole = $this->option('role');
        $logMissing = $this->option('log-missing');
        $reportOnly = $this->option('report-only');

        $routes = Route::getRoutes();
        $unprotected = [];

        foreach ($routes as $route) {
            $uri = $route->uri();
            // Only check routes that look sensitive (contain admin, dashboard, compliance, etc.)
            if (!$this->isSensitiveRoute($uri)) {
                continue;
            }

            $hasMiddleware = false;
            $middlewares = $route->gatherMiddleware();
            foreach ($middlewares as $mw) {
                if (str_contains($mw, 'compliance.role') || str_contains($mw, 'can:') || $mw === 'auth') {
                    $hasMiddleware = true;
                    break;
                }
            }

            // Also check if route uses a Gate in controller – we cannot statically detect that.
            // So we only note missing middleware.
            if (!$hasMiddleware) {
                $unprotected[] = $uri;
                if ($logMissing && !$reportOnly) {
                    ComplianceEngine::log([
                        'type' => 'MISSING_AUTHORIZATION',
                        'score' => 60,
                        'severity' => 'MEDIUM',
                        'attempts' => 0,
                        'meta' => [
                            'route' => $uri,
                            'recommended_role' => $requiredRole,
                            'explanation' => "Route {$uri} has no role middleware. Potential bypass.",
                        ],
                        'recommendation' => "Add middleware 'compliance.role' to route {$uri}",
                    ]);
                }
            }
        }

        if ($reportOnly || !$logMissing) {
            $this->info('Routes without role protection:');
            foreach ($unprotected as $uri) {
                $this->line(" - {$uri}");
            }
            $this->info('Total unprotected sensitive routes: ' . count($unprotected));
        } else {
            $this->info('Logged ' . count($unprotected) . ' missing authorization events.');
        }

        return Command::SUCCESS;
    }

    protected function isSensitiveRoute(string $uri): bool
    {
        $patterns = config('compliance.rbac.enforce_on_routes_containing', [
            'dashboard',
            'admin',
            'compliance',
            'security'
        ]);
        foreach ($patterns as $pattern) {
            if (str_contains($uri, $pattern)) {
                return true;
            }
        }
        return false;
    }
}
