<?php

namespace GhanaCompliance\Act843SDK\Http\Controllers;

use GhanaCompliance\Act843SDK\Models\ComplianceLog;
use GhanaCompliance\Act843SDK\Models\IpReputation;
use GhanaCompliance\Act843SDK\Services\ComplianceHealthService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ComplianceReportController
{
    public function generate(Request $request)
    {
        $from = $request->get('from', Carbon::now()->subMonth());
        $to = $request->get('to', Carbon::now());
        $type = $request->get('type', 'full'); // full, summary, technical

        // Collect data
        $logs = ComplianceLog::whereBetween('created_at', [$from, $to])->get();
        $topIps = IpReputation::orderByDesc('score')->limit(10)->get();
        $health = app(ComplianceHealthService::class)->getHealthMetrics();

        $stats = [
            'total_events' => $logs->count(),
            'high_risk' => $logs->where('severity', 'HIGH')->count(),
            'medium_risk' => $logs->where('severity', 'MEDIUM')->count(),
            'low_risk' => $logs->where('severity', 'LOW')->count(),
            'unique_ips' => $logs->unique('ip_address')->count(),
            'avg_score' => round($logs->avg('score') ?? 0),
        ];

        $data = compact('from', 'to', 'stats', 'topIps', 'health', 'type');

        // Check if PDF package is installed (optional)
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = app('dompdf.wrapper')->loadView('compliance::report', $data);
            $pdf->setPaper('A4', 'portrait');
            return $pdf->download('compliance_report_' . now()->format('Ymd_His') . '.pdf');
        } else {
            // Fallback: return a JSON response with a helpful message
            return response()->json([
                'error' => 'PDF generation requires barryvdh/laravel-dompdf. Please install it via `composer require barryvdh/laravel-dompdf`.'
            ], 500);
        }
    }
}
