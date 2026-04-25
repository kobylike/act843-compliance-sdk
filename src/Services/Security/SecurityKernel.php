<?php

namespace GhanaCompliance\Act843SDK\Services\Security;

use GhanaCompliance\Act843SDK\Models\SecurityEvent;

class SecurityKernel
{
    public static function track(array $data): void
    {
        SecurityEvent::create([
            'ip' => $data['ip'],
            'path' => $data['path'],
            'method' => $data['method'],
        ]);
    }
}
