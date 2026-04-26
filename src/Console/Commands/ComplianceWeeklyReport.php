<?php

namespace GhanaCompliance\Act843SDK\Console\Commands;

use GhanaCompliance\Act843SDK\Models\ComplianceLog;
use GhanaCompliance\Act843SDK\Models\IpReputation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ComplianceWeeklyReport extends Command
{
    protected $signature = 'compliance:weekly-report';
    protected $description = 'Send weekly compliance summary email';

    public function handle()
    {
        $start = Carbon::now()->subWeek();
        $logs = ComplianceLog::where('created_at', '>=', $start)->get();

        $stats = [
            'total_events' => $logs->count(),
            'high_risk_count' => $logs->where('severity', 'HIGH')->count(),
            'medium_risk_count' => $logs->where('severity', 'MEDIUM')->count(),
            'unique_ips' => $logs->unique('ip_address')->count(),
            'top_ips' => IpReputation::orderByDesc('score')->limit(5)->get(),
        ];

        Mail::raw($this->renderEmail($stats), function ($mail) {
            $mail->to(config('compliance.report_email', 'admin@example.com'))
                ->subject('Weekly Compliance Report');
        });

        $this->info('Weekly report sent.');
    }

    protected function renderEmail(array $stats): string
    {
        return "Weekly Compliance Report\n"
            . "Period: last 7 days\n"
            . "Total events: {$stats['total_events']}\n"
            . "High risk: {$stats['high_risk_count']}\n"
            . "Medium risk: {$stats['medium_risk_count']}\n"
            . "Unique IPs: {$stats['unique_ips']}\n"
            . "Top risky IPs:\n"
            . collect($stats['top_ips'])->map(fn($ip) => " - {$ip->ip} (score {$ip->score})")->implode("\n");
    }
}
