<?php

namespace GhanaCompliance\Act843SDK\Livewire;

use GhanaCompliance\Act843SDK\Models\ComplianceLog;
use GhanaCompliance\Act843SDK\Models\IpReputation;
use GhanaCompliance\Act843SDK\Models\SecurityAlert;
use GhanaCompliance\Act843SDK\Services\ComplianceHealthService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class SecurityDashboard extends Component
{
    use WithPagination;

    public $filterSeverity = '';
    public $filterType = '';
    public $dateRange = 'today';
    public $autoRefresh = true;
    public $stats = [];
    public $complianceHealth = [];

    protected $listeners = ['refreshDashboard' => 'loadStats'];

    public function mount()
    {
        $this->loadStats();
        $this->loadComplianceHealth();
    }

    public function loadStats()
    {
        $query = ComplianceLog::query();

        switch ($this->dateRange) {
            case 'today':
                $query->whereDate('created_at', Carbon::today());
                break;
            case 'week':
                $query->where('created_at', '>=', Carbon::now()->subWeek());
                break;
            case 'month':
                $query->where('created_at', '>=', Carbon::now()->subMonth());
                break;
        }

        $this->stats = [
            'total_threats' => (clone $query)->count(),
            'high_risk' => (clone $query)->where('severity', 'HIGH')->count(),
            'medium_risk' => (clone $query)->where('severity', 'MEDIUM')->count(),
            'low_risk' => (clone $query)->where('severity', 'LOW')->count(),
            'unique_ips' => (clone $query)->distinct('ip_address')->count('ip_address'),
            'avg_score' => round((clone $query)->avg('score') ?? 0),
            'active_alerts' => SecurityAlert::unresolved()->count(),
        ];
    }

    public function loadComplianceHealth()
    {
        $this->complianceHealth = app(ComplianceHealthService::class)->getHealthMetrics();
    }

    public function runComplianceScans()
    {
        Artisan::call('compliance:scan-passwords');
        Artisan::call('compliance:scan-retention');
        $this->loadComplianceHealth();
        $this->dispatch('notify', 'Compliance scans completed', 'success');
    }

    #[On('echo:security,AlertEvent')]
    public function refreshAlerts()
    {
        $this->dispatch('$refresh');
    }

    public function getChartData()
    {
        if ($this->dateRange === 'week') {
            return ComplianceLog::selectRaw('DATE(created_at) as date, AVG(score) as avg_score')
                ->where('created_at', '>=', Carbon::now()->subWeek())
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn($item) => ['label' => $item->date, 'score' => $item->avg_score]);
        }

        $data = ComplianceLog::selectRaw('HOUR(created_at) as hour, AVG(score) as avg_score')
            ->whereDate('created_at', now())
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(fn($item) => ['label' => $item->hour . ':00', 'score' => $item->avg_score]);

        if ($data->isEmpty()) {
            return collect([['label' => now()->format('H:00'), 'score' => 0]]);
        }
        return $data;
    }

    public function getAttackTypeDistribution()
    {
        return ComplianceLog::selectRaw('type, COUNT(*) as total')
            ->when($this->dateRange === 'today', fn($q) => $q->whereDate('created_at', Carbon::today()))
            ->when($this->dateRange === 'week', fn($q) => $q->where('created_at', '>=', Carbon::now()->subWeek()))
            ->groupBy('type')
            ->limit(10)
            ->get();
    }

    public function getComplianceTrendData()
    {
        return ComplianceLog::whereIn('type', ['PASSWORD_POLICY_SCAN', 'DATA_RETENTION_SCAN'])
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn($log) => $log->created_at->format('Y-m-d'))
            ->map(fn($logs) => round($logs->avg('score')))
            ->toArray();
    }

    public function exportCsv()
    {
        $logs = ComplianceLog::latest()->take(5000)->get();
        $filename = 'security_logs_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID', 'Type', 'IP', 'Score', 'Severity', 'Attempts', 'Created At']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->type,
                    $log->ip_address,
                    $log->score,
                    $log->severity,
                    $log->attempts,
                    $log->created_at,
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        $alerts = SecurityAlert::unresolved()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $query = ComplianceLog::query();
        if ($this->filterSeverity) $query->where('severity', $this->filterSeverity);
        if ($this->filterType) $query->where('type', $this->filterType);

        return view('livewire.security-dashboard', [
            'logs' => $query->latest()->paginate(20),
            'ips' => IpReputation::orderByDesc('score')->limit(15)->get(),
            'chartData' => $this->getChartData(),
            'attackTypes' => $this->getAttackTypeDistribution(),
            'alerts' => $alerts,
            'complianceHealth' => $this->complianceHealth,
            'complianceTrend' => $this->getComplianceTrendData(),
        ]);
    }
}
