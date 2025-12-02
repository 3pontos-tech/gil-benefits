<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use App\Services\Logging\StructuredLogger;
use Illuminate\Support\Facades\Log;

class AlertManager
{
    public function __construct(
        private readonly StructuredLogger $logger
    ) {}

    public function sendAlert(string $title, array $data, string $severity = 'warning'): void
    {
        $alert = [
            'title' => $title,
            'severity' => $severity,
            'data' => $data,
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'application' => config('app.name'),
        ];

        // Log the alert
        $this->logger->logSystemEvent('alert_triggered', $alert, $severity);

        // Send notifications based on severity
        match ($severity) {
            'critical' => $this->sendCriticalAlert($alert),
            'error' => $this->sendErrorAlert($alert),
            'warning' => $this->sendWarningAlert($alert),
            default => $this->sendInfoAlert($alert),
        };
    }

    private function sendCriticalAlert(array $alert): void
    {
        // Log to critical channel
        Log::channel('emergency')->critical($alert['title'], $alert);

        // Send email notification if configured
        $this->sendEmailAlert($alert);

        // Send Slack notification if configured
        $this->sendSlackAlert($alert);

        // You could add other notification channels here (SMS, PagerDuty, etc.)
    }

    private function sendErrorAlert(array $alert): void
    {
        // Log to error channel
        Log::error($alert['title'], $alert);

        // Send email notification for errors in production
        if (app()->isProduction()) {
            $this->sendEmailAlert($alert);
        }

        // Send Slack notification
        $this->sendSlackAlert($alert);
    }

    private function sendWarningAlert(array $alert): void
    {
        // Log warning
        Log::warning($alert['title'], $alert);

        // Send Slack notification for warnings
        $this->sendSlackAlert($alert);
    }

    private function sendInfoAlert(array $alert): void
    {
        // Just log info alerts
        Log::info($alert['title'], $alert);
    }

    private function sendEmailAlert(array $alert): void
    {
        try {
            $recipients = $this->getAlertRecipients($alert['severity']);

            if (empty($recipients)) {
                return;
            }

            // You would implement your email notification here
            // For now, we'll just log that we would send an email
            $this->logger->logSystemEvent('email_alert_sent', [
                'alert_title' => $alert['title'],
                'severity' => $alert['severity'],
                'recipients' => $recipients,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send email alert', [
                'alert' => $alert,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendSlackAlert(array $alert): void
    {
        try {
            $webhookUrl = config('logging.channels.slack.url');

            if (! $webhookUrl) {
                return;
            }

            $color = $this->getSlackColor($alert['severity']);
            $emoji = $this->getSlackEmoji($alert['severity']);

            $payload = [
                'text' => "{$emoji} {$alert['title']}",
                'attachments' => [
                    [
                        'color' => $color,
                        'fields' => [
                            [
                                'title' => 'Severity',
                                'value' => strtoupper($alert['severity']),
                                'short' => true,
                            ],
                            [
                                'title' => 'Environment',
                                'value' => $alert['environment'],
                                'short' => true,
                            ],
                            [
                                'title' => 'Application',
                                'value' => $alert['application'],
                                'short' => true,
                            ],
                            [
                                'title' => 'Timestamp',
                                'value' => $alert['timestamp'],
                                'short' => true,
                            ],
                        ],
                        'footer' => 'System Monitor',
                        'ts' => now()->timestamp,
                    ],
                ],
            ];

            // Add alert data as attachment if present
            if (! empty($alert['data'])) {
                $payload['attachments'][0]['fields'][] = [
                    'title' => 'Details',
                    'value' => '```' . json_encode($alert['data'], JSON_PRETTY_PRINT) . '```',
                    'short' => false,
                ];
            }

            // You would send this to Slack here
            // For now, we'll just log that we would send to Slack
            $this->logger->logSystemEvent('slack_alert_sent', [
                'alert_title' => $alert['title'],
                'severity' => $alert['severity'],
                'payload_size' => strlen(json_encode($payload)),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send Slack alert', [
                'alert' => $alert,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getAlertRecipients(string $severity): array
    {
        $recipients = config('monitoring.alert_recipients', []);

        return match ($severity) {
            'critical' => $recipients['critical'] ?? $recipients['default'] ?? [],
            'error' => $recipients['error'] ?? $recipients['default'] ?? [],
            'warning' => $recipients['warning'] ?? [],
            default => [],
        };
    }

    private function getSlackColor(string $severity): string
    {
        return match ($severity) {
            'critical' => 'danger',
            'error' => 'danger',
            'warning' => 'warning',
            default => 'good',
        };
    }

    private function getSlackEmoji(string $severity): string
    {
        return match ($severity) {
            'critical' => '🚨',
            'error' => '❌',
            'warning' => '⚠️',
            default => 'ℹ️',
        };
    }
}
