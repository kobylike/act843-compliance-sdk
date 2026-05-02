<?php

namespace GhanaCompliance\Act843SDK\Livewire;

use Carbon\Carbon;
use GhanaCompliance\Act843SDK\Models\ComplianceLog;
use GhanaCompliance\Act843SDK\Models\IpReputation;
use GhanaCompliance\Act843SDK\Models\SecurityAlert;
use GhanaCompliance\Act843SDK\Services\ComplianceHealthService;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;


class SecurityDashboard extends Component
{
    use WithPagination;

    public $filterSeverity = '';
    public $filterType = '';
    public $dateRange = 'today';
    public $autoRefresh = true;
    public $simpleMode = false;          // Simple Mode toggle
    public $stats = [];
    public $complianceHealth = [];

    protected $listeners = ['refreshDashboard' => 'loadStats'];
    public $showFixModal = false;

    public function toggleFixModal()
    {
        $this->showFixModal = !$this->showFixModal;
    }
    public function mount()
    {
        $this->loadStats();
        $this->loadComplianceHealth();
        // Load simple mode preference from session
        $this->simpleMode = session('compliance_simple_mode', false);
    }

    public function toggleSimpleMode()
    {
        $this->simpleMode = !$this->simpleMode;
        session(['compliance_simple_mode' => $this->simpleMode]);
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

    public function runDeepScan()
    {
        if (!config('compliance.allow_deep_password_scan', false)) {
            $this->dispatch('notify', 'Deep scanning disabled. Set ALLOW_DEEP_PASSWORD_SCAN=true in .env', 'error');
            return;
        }
        Artisan::call('compliance:scan-passwords', ['--deep' => true, '--force' => true]);
        $this->loadComplianceHealth();
        $this->dispatch('notify', 'Deep password scan completed.', 'success');
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

    /**
     * Get plain‑language executive summary for non‑technical users.
     */
    public function getExecutiveSummary()
    {
        $health = $this->complianceHealth;
        $score = $health['score'];
        $grade = $health['grade'];
        $weakHashes = $health['password_policy']['weak_hashes'];
        $weakPolicies = $health['password_policy']['weak_policies'];
        $nonCompliantTables = $health['data_retention']['non_compliant'];

        $statusColor = $score >= 80 ? 'green' : ($score >= 60 ? 'yellow' : 'red');
        $statusText = $score >= 80 ? 'Good' : ($score >= 60 ? 'Needs attention' : 'Critical issues');

        $alerts = [];

        if ($weakHashes > 0) {
            $alerts[] = [
                'severity' => 'high',
                'message' => "{$weakHashes} user passwords are stored in a weak format (plain text or MD5). Fix immediately.",
                'action' => 'Run `php artisan compliance:scan-passwords --deep --force` and then re‑hash passwords.',
                'action_label' => 'How to fix',
            ];
        }

        if ($weakPolicies > 0) {
            $alerts[] = [
                'severity' => 'medium',
                'message' => 'Your password policy is missing some security rules (min length or complexity).',
                'action' => 'Check config/compliance.php and enforce min length 12 and complexity.',
                'action_label' => 'Fix policy',
            ];
        }

        if ($nonCompliantTables > 0) {
            $alerts[] = [
                'severity' => 'medium',
                'message' => "{$nonCompliantTables} database tables have data older than allowed retention period.",
                'action' => 'Run `php artisan compliance:purge` to delete old records.',
                'action_label' => 'Clean up',
            ];
        }

        if (empty($alerts)) {
            $alerts[] = [
                'severity' => 'low',
                'message' => 'All compliance checks passed. Your system is compliant with Act 843.',
                'action' => '',
                'action_label' => '',
            ];
        }

        return [
            'score' => $score,
            'grade' => $grade,
            'status_color' => $statusColor,
            'status_text' => $statusText,
            'alerts' => $alerts,
        ];
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

        // In simple mode, only show medium/high severity logs and hide routine scans (score 10)
        if ($this->simpleMode) {
            $query->where('severity', '!=', 'LOW')
                ->orWhere(function ($q) {
                    $q->where('severity', 'LOW')
                        ->whereNotIn('type', ['PASSWORD_POLICY_SCAN', 'DATA_RETENTION_SCAN']);
                });
        }

        return view('compliance::livewire.security-dashboard', [
            'logs' => $query->latest()->paginate(20),
            'ips' => IpReputation::orderByDesc('score')->limit(15)->get(),
            'chartData' => $this->getChartData(),
            'attackTypes' => $this->getAttackTypeDistribution(),
            'alerts' => $alerts,
            'complianceHealth' => $this->complianceHealth,
            'complianceTrend' => $this->getComplianceTrendData(),
            'executiveSummary' => $this->getExecutiveSummary(),
        ]);
    }
}
