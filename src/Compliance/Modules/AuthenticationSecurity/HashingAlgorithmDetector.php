<?php
// app/Compliance/Modules/AuthenticationSecurity/HashingAlgorithmDetector.php

namespace GhanaCompliance\Act843SDK\Compliance\Modules\AuthenticationSecurity;

class HashingAlgorithmDetector
{
    // Detection without storing actual hash
    public function detectAlgorithm(string $hash): string
    {
        if (preg_match('/^[a-f0-9]{32}$/', $hash)) return 'MD5';
        if (preg_match('/^[a-f0-9]{40}$/', $hash)) return 'SHA1';
        if (preg_match('/^\$2[aby]\$\d+\$/', $hash)) return 'BCRYPT';
        if (preg_match('/^\$argon2/', $hash)) return 'ARGON2';
        if (preg_match('/^\*[0-9A-F]{40}$/', $hash)) return 'MySQL OLD';
        return 'UNKNOWN';
    }
}
