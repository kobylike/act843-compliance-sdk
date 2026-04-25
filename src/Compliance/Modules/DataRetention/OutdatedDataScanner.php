<?php
// app/Compliance/Modules/DataRetention/OutdatedDataScanner.php

namespace GhanaCompliance\Act843SDK\Compliance\Modules\DataRetention;

use GhanaCompliance\Act843SDK\Models\ComplianceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OutdatedDataScanner
{
    public function scan(string $table, string $dateColumn = 'created_at', int $thresholdDays = 365): array
    {
        $cutoff = Carbon::now()->subDays($thresholdDays);
        $count = DB::table($table)->where($dateColumn, '<', $cutoff)->count();

        if ($count > 0) {
            ComplianceLog::create([
                'type' => 'OUTDATED_DATA_DETECTED',
                'ip_address' => 'system',
                'score' => min(100, $count / 100),
                'severity' => $count > 1000 ? 'HIGH' : 'MEDIUM',
                'attempts' => 0,
                'meta' => [
                    'table' => $table,
                    'outdated_records' => $count,
                    'threshold_days' => $thresholdDays,
                ],
                'recommendation' => "Archive or delete {$count} outdated records from {$table}.",
            ]);
        }

        return ['table' => $table, 'outdated_count' => $count];
    }
}
