<?php

namespace GhanaCompliance\Act843SDK\Services\Security;

class AutomatedResponse
{
    public function handle(array $analysis): array
    {
        $score = $analysis['score'];

        // Generate recommendations only – NO actual blocking/rate limiting
        $recommendations = $this->generateRecommendations($score);

        return [
            'actions_taken' => [], // No actions taken
            'recommendations' => $recommendations,
            'enforcement' => false,
        ];
    }

    protected function generateRecommendations(int $score): array
    {
        if ($score >= 90) {
            return [
                'action' => 'RECOMMEND_MANUAL_REVIEW',
                'message' => 'Critical threat detected – recommend manual investigation',
                'suggestions' => [
                    'Consider temporary block after investigation',
                    'Notify security team',
                    'Enable CAPTCHA on login forms',
                ],
            ];
        }

        if ($score >= 70) {
            return [
                'action' => 'RECOMMEND_MONITORING',
                'message' => 'Suspicious activity – increase monitoring',
                'suggestions' => [
                    'Add login delay if this persists',
                    'Review IP in dashboard',
                ],
            ];
        }

        if ($score >= 50) {
            return [
                'action' => 'RECOMMEND_LOG',
                'message' => 'Elevated risk – keep logging',
                'suggestions' => ['Continue observation'],
            ];
        }

        return [
            'action' => 'NORMAL',
            'message' => 'No action recommended',
            'suggestions' => [],
        ];
    }
}
