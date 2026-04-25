<?php

namespace GhanaCompliance\Act843SDK\Livewire;

use Livewire\Component;
use GhanaCompliance\Act843SDK\Models\ComplianceLog;

class IpProfile extends Component
{
    public $ip;
    public $stats = [];

    public function mount($ip)
    {
        $this->ip = $ip;
        $this->loadStats();
    }

    public function loadStats()
    {
        $logs = ComplianceLog::where('ip_address', $this->ip);

        $this->stats = [
            'total_attacks' => $logs->count(),
            'avg_score' => round($logs->avg('score') ?? 0),
            'max_score' => $logs->max('score') ?? 0,
            'last_seen' => optional($logs->latest()->first())->created_at,
        ];
    }

    public function getChartData()
    {
        return ComplianceLog::selectRaw('DATE(created_at) as date, AVG(score) as score')
            ->where('ip_address', $this->ip)
            ->groupBy('date')
            ->pluck('score', 'date');
    }

    public function getAttackTypes()
    {
        return ComplianceLog::selectRaw('type, COUNT(*) as total')
            ->where('ip_address', $this->ip)
            ->groupBy('type')
            ->pluck('total', 'type');
    }

    public function render()
    {
        return view('livewire.ip-profile', [
            'logs' => ComplianceLog::where('ip_address', $this->ip)
                ->latest()
                ->take(20)
                ->get(),
            'chartData' => $this->getChartData(),
            'attackTypes' => $this->getAttackTypes(),
        ]);
    }
}
