<?php
// app/Compliance/SDK/AnalyzerProxy.php

namespace GhanaCompliance\Act843SDK\Compliance\SDK;

use GhanaCompliance\Act843SDK\Services\Security\ComplianceAnalyzer;
use GhanaCompliance\Act843SDK\Models\IpReputation;

class AnalyzerProxy
{
    public function analyze(string $ip, int $attempts): array
    {
        $analyzer = new ComplianceAnalyzer();
        return $analyzer->analyze([
            'ip' => $ip,
            'attempts' => $attempts,
            'type' => 'SDK_REQUEST',
        ]);
    }

    public function getReputation(string $ip): array
    {
        $record = IpReputation::where('ip', $ip)->first();
        return $record ? $record->toArray() : ['score' => 0, 'risk_level' => 'LOW'];
    }
}
