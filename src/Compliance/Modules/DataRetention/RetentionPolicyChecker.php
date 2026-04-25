<?php

namespace GhanaCompliance\Act843SDK\Compliance\Modules\DataRetention;

use GhanaCompliance\Act843SDK\Models\ComplianceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RetentionPolicyChecker
{
    public function analyze(): array
    {
        $results = [];
        $tables = $this->getAllTables();

        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'created_at')) {
                continue;
            }

            $oldest = DB::table($table)->min('created_at');
            if ($oldest) {
                $ageDays = Carbon::parse($oldest)->diffInDays(now());
                $policy = config("compliance.retention.{$table}_days", null);
                $results[] = [
                    'table' => $table,
                    'oldest_record_days' => round($ageDays, 2),
                    'retention_policy' => $policy,
                    'compliance' => $policy ? $ageDays <= $policy : true,
                ];
            }
        }

        $nonCompliant = collect($results)->filter(fn($r) => !$r['compliance'])->count();
        ComplianceLog::create([
            'type' => 'DATA_RETENTION_SCAN',
            'ip_address' => 'system',
            'score' => $nonCompliant > 0 ? 60 : 10,
            'severity' => $nonCompliant > 0 ? 'MEDIUM' : 'LOW',
            'attempts' => 0,
            'meta' => ['scan_results' => $results, 'non_compliant_tables' => $nonCompliant],
            'recommendation' => $nonCompliant > 0 ? 'Implement automated data purging based on retention policy.' : null,
        ]);

        return $results;
    }

    protected function getAllTables(): array
    {
        $database = DB::getDatabaseName();
        $rows = DB::select("SHOW TABLES");
        $key = "Tables_in_{$database}";
        return array_map(fn($row) => $row->$key, $rows);
    }
}
