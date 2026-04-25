<?php

namespace GhanaCompliance\Act843SDK\Services\Security;

use GhanaCompliance\Act843SDK\Models\SecurityAlert;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertService
{
    public function send(string $severity, string $title, string $message, array $context = []): void
    {
        // Store in database
        $alert = SecurityAlert::create([
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'context' => $context,
            'is_resolved' => false,
        ]);

        // Send based on severity
        if ($severity === 'HIGH' && config('security.alert_high_to_email', true)) {
            $this->sendEmail($title, $message, $context);
        }

        if ($severity === 'HIGH' && config('security.alert_to_slack', false)) {
            $this->sendSlack($title, $message, $context);
        }

        // Always log high severity
        if ($severity === 'HIGH') {
            Log::channel('security')->warning($title, ['message' => $message, 'context' => $context]);
        }
    }

    protected function sendEmail(string $title, string $message, array $context): void
    {
        $emails = config('security.alert_emails', []);
        if (empty($emails)) return;

        try {
            Mail::raw("SECURITY ALERT: {$title}\n\n{$message}\n\nContext: " . json_encode($context), function ($mail) use ($emails, $title) {
                $mail->to($emails)->subject("[SECURITY] {$title}");
            });
        } catch (\Exception $e) {
            Log::error("Failed to send security alert email: " . $e->getMessage());
        }
    }

    protected function sendSlack(string $title, string $message, array $context): void
    {
        $webhook = config('security.slack_webhook');
        if (!$webhook) return;

        try {
            Http::post($webhook, [
                'text' => "*🚨 SECURITY ALERT*: {$title}\n{$message}\n```" . json_encode($context, JSON_PRETTY_PRINT) . "```",
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send Slack alert: " . $e->getMessage());
        }
    }

    public function resolve(int $alertId, string $resolution): void
    {
        $alert = SecurityAlert::find($alertId);
        if ($alert) {
            $alert->update([
                'is_resolved' => true,
                'resolved_at' => now(),
                'resolution_notes' => $resolution,
                'resolved_by' => Auth::id(),
            ]);
        }
    }
}
