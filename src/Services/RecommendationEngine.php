<?php

namespace GhanaCompliance\Act843SDK\Services;

class RecommendationEngine
{
    public function generate(array $analysis): array
    {
        if ($analysis['score'] >= 90) {
            return [
                'action' => 'CRITICAL_MONITORING',
                'message' => 'Severe attack pattern detected',
                'steps' => [
                    '1. Enable CAPTCHA: add `gregwar/captcha` and include in login form',
                    '2. Apply rate limiting: `Route::post(\'/login\')->middleware(\'throttle:10,1\')`',
                    '3. Notify admin: run `php artisan compliance:alert --severity=critical`',
                ],
                'recommendations' => ['Enable CAPTCHA', 'Enable rate limiting', 'Notify admin'],
            ];
        }

        if ($analysis['score'] >= 70) {
            return [
                'action' => 'INCREASE_MONITORING',
                'message' => 'Suspicious activity detected',
                'steps' => [
                    '1. Monitor IP: visit `/ip/{ip}` dashboard',
                    '2. Add login delay: use `Illuminate\\Auth\\Events\\Attempting` listener to sleep(2)',
                ],
                'recommendations' => ['Monitor IP closely', 'Add login delay'],
            ];
        }

        return [
            'action' => 'NORMAL',
            'message' => 'No action required',
            'steps' => [],
            'recommendations' => [],
        ];
    }
}
