<?php

namespace GhanaCompliance\Act843SDK\Livewire;

use GhanaCompliance\Act843SDK\Models\ComplianceLog;
use Carbon\Carbon;
use Livewire\Component;

class AttackGraph extends Component
{
    public $timeRange = 'today'; // today, week, month
    public $graphData = [];

    public function mount()
    {
        $this->loadGraphData();
    }

    public function updatedTimeRange()
    {
        $this->loadGraphData();
    }

    public function loadGraphData()
    {
        $start = match ($this->timeRange) {
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->subMonth(),
            default => Carbon::today(),
        };

        $logs = ComplianceLog::where('created_at', '>=', $start)
            ->orderBy('created_at')
            ->get(['id', 'ip_address', 'type', 'score', 'severity', 'created_at']);

        $nodes = [];
        $edges = [];
        $nodeSet = [];
        $edgeSet = [];

        foreach ($logs as $log) {
            $ip = $log->ip_address;
            $attackType = $log->type;
            $timeKey = $log->created_at->format('Y-m-d H:00:00'); // hourly grouping

            // Node: IP
            if (!isset($nodeSet["ip:$ip"])) {
                $nodeSet["ip:$ip"] = true;
                $nodes[] = [
                    'id' => "ip:$ip",
                    'label' => $ip,
                    'group' => 'ip',
                    'title' => "IP: $ip\nScore: {$log->score}\nSeverity: {$log->severity}",
                    'size' => min(30, 10 + $log->score / 5),
                ];
            }

            // Node: Attack Type (aggregated)
            $typeId = "type:$attackType";
            if (!isset($nodeSet[$typeId])) {
                $nodeSet[$typeId] = true;
                $nodes[] = [
                    'id' => $typeId,
                    'label' => $attackType,
                    'group' => 'attack',
                    'title' => "Attack type: $attackType",
                    'size' => 20,
                ];
            }

            // Edge: IP → Attack Type
            $edgeId = "edge:ip:$ip:type:$attackType";
            if (!isset($edgeSet[$edgeId])) {
                $edgeSet[$edgeId] = 1;
                $edges[] = [
                    'from' => "ip:$ip",
                    'to' => $typeId,
                    'value' => 1,
                    'title' => "First occurrence: {$log->created_at->diffForHumans()}",
                ];
            } else {
                // increment weight
                foreach ($edges as &$edge) {
                    if ($edge['from'] === "ip:$ip" && $edge['to'] === $typeId) {
                        $edge['value']++;
                        $edge['title'] = "Occurrences: {$edge['value']}";
                        break;
                    }
                }
            }
        }

        $this->graphData = [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }

    public function render()
    {
        return view('compliance::livewire.attack-graph');
    }
}
