<?php

namespace GhanaCompliance\Act843SDK\Console\Commands;

use GhanaCompliance\Act843SDK\Models\ComplianceLog;
use GhanaCompliance\Act843SDK\Models\IpReputation;
use GhanaCompliance\Act843SDK\Services\ComplianceHealthService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ComplianceSendReport extends Command
{
    protected $signature = 'compliance:send-report {--api-key= : API key for regulator authentication} {--api-url= : Regulator API URL} {--date= : Specific report date (Y-m-d)}';
    protected $description = 'Send anonymised compliance summary to the regulator API';

    public function handle()
    {
        $apiUrl = $this->option('api-url') ?? config('compliance.regulator_api_url');
        $apiKey = $this->option('api-key') ?? config('compliance.regulator_api_key');

        if (!$apiUrl || !$apiKey) {
            $this->error('Regulator API URL and Key must be set in config/compliance.php or passed as options.');
            return 1;
        }

        $date = $this->option('date') ?? now()->toDateString();
        $start = Carbon::parse($date)->startOfDay();
        $end = Carbon::parse($date)->endOfDay();

        $logs = ComplianceLog::whereBetween('created_at', [$start, $end])->get();
        $failedLogins = $logs->where('type', 'BRUTE_FORCE')->count();
        $highRiskIps = IpReputation::where('score', '>=', 80)->count();
        $avgRiskScore = round($logs->avg('score') ?? 0, 1);

        $health = app(ComplianceHealthService::class)->getHealthMetrics();
        $weakPasswordPolicy = $health['password_policy']['status'] !== '✅ Compliant';
        $retentionViolations = $health['data_retention']['non_compliant'];

        $payload = [
            'report_date' => $date,
            'total_failed_logins' => $failedLogins,
            'high_risk_ips' => $highRiskIps,
            'avg_risk_score' => $avgRiskScore,
            'weak_password_policy' => $weakPasswordPolicy,
            'retention_violations' => $retentionViolations,
            'extra_metrics' => [
                'total_logs' => $logs->count(),
                'unique_ips' => $logs->unique('ip_address')->count(),
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ])->post($apiUrl, $payload);

            if ($response->successful()) {
                $this->info('Report sent successfully. Response: ' . $response->json('message'));
                return 0;
            } else {
                $this->error('Failed to send report. HTTP ' . $response->status());
                $this->error($response->body());
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Exception: ' . $e->getMessage());
            return 1;
        }
    }
}
