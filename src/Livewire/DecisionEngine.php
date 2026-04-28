<?php

namespace GhanaCompliance\Act843SDK\Livewire;

use GhanaCompliance\Act843SDK\Models\Remediation;
use GhanaCompliance\Act843SDK\Services\ComplianceHealthService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Livewire\Component;

class DecisionEngine extends Component
{
    public $recommendations = [];
    public $messages = [];

    public function mount()
    {
        $this->loadRecommendations();
    }

    public function loadRecommendations()
    {
        $health = app(ComplianceHealthService::class)->getHealthMetrics();
        $this->recommendations = $health['recommendations'] ?? [];
    }

    public function applyFix($fixKey)
    {
        $finding = '';
        $action = '';
        $success = false;

        try {
            switch ($fixKey) {
                case 'min_length':
                    $finding = 'Weak password minimum length';
                    $action = 'Set PASSWORD_MIN_LENGTH=12 in .env';
                    $success = $this->setEnvValue('PASSWORD_MIN_LENGTH', '12');
                    break;

                case 'complexity':
                    $finding = 'Missing password complexity';
                    $action = 'Set PASSWORD_COMPLEXITY=true in .env';
                    $success = $this->setEnvValue('PASSWORD_COMPLEXITY', 'true');
                    break;

                case 'retention':
                    $finding = 'Missing data retention policy';
                    $action = 'Added retention policy for compliance_logs';
                    $success = $this->addRetentionPolicy();
                    break;

                default:
                    $this->addMessage('error', 'Unknown fix key.');
                    return;
            }

            if ($success) {
                // Log remediation
                Remediation::create([
                    'finding' => $finding,
                    'action_taken' => $action,
                    'user_id' => Auth::id(),
                    'resolved_at' => now(),
                ]);
                $this->addMessage('success', "✅ {$finding} fixed. Action: {$action}");
                $this->loadRecommendations(); // refresh recommendations
            } else {
                $this->addMessage('error', "❌ Failed to apply fix for: {$finding}");
            }
        } catch (\Exception $e) {
            $this->addMessage('error', 'An error occurred while applying the fix.');
            logger("DecisionEngine error: " . $e->getMessage());
        }
    }

    /**
     * Set or add an environment variable in .env file.
     */
    protected function setEnvValue($key, $value): bool
    {
        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            return false;
        }

        $content = File::get($envPath);
        if (preg_match("/^{$key}=.*/m", $content)) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            $content .= "\n{$key}={$value}\n";
        }
        File::put($envPath, $content);

        // Refresh Laravel's config caches
        Artisan::call('config:clear');
        return true;
    }

    /**
     * Add default retention policy to config/compliance.php
     */
    protected function addRetentionPolicy(): bool
    {
        $configPath = config_path('compliance.php');
        if (!File::exists($configPath)) {
            return false;
        }

        $content = File::get($configPath);
        // Simple: add retention for compliance_logs if missing
        if (!str_contains($content, "'compliance_logs' =>")) {
            $newRetention = "'retention' => [\n        'compliance_logs' => 90,\n        'security_events' => 30,\n        'ip_reputations' => 180,\n    ],";
            $content = preg_replace("/'retention' => \[(.*?)\],/s", $newRetention, $content);
            File::put($configPath, $content);
            return true;
        }
        return false;
    }

    protected function addMessage($type, $text)
    {
        $this->messages[] = ['type' => $type, 'text' => $text];
        session()->flash('decision_message', $this->messages);
    }

    public function render()
    {
        return view('livewire.decision-engine');
    }
}
