<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;

class HealthCheckAlert extends Notification
{
    use Queueable;

    public string $status;
    public array $checks;
    public float $totalLatencyMs;
    public string $environment;

    public function __construct(
        string $status,
        array $checks,
        float $totalLatencyMs,
        string $environment
    ) {
        $this->status = $status;
        $this->checks = $checks;
        $this->totalLatencyMs = $totalLatencyMs;
        $this->environment = $environment;
    }

    public function via(object $notifiable): array
    {
        $channels = ['log'];
        // Gunakan config untuk menentukan channel
        if (config('monitoring.health_check.alert_channels.slack', false)) {
            $channels[] = 'slack';
        }
        if (config('monitoring.health_check.alert_channels.mail', false)) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("[AUREX {$this->environment}] Health Alert: {$this->status}")
            ->greeting("Health Check Alert ({$this->environment})")
            ->line("Status: **{$this->status}**")
            ->line("Total Latency: {$this->totalLatencyMs}ms");

        foreach ($this->checks as $service => $check) {
            $status = $check['status'];
            $latency = $check['latency_ms'] ?? '-';
            $mail->line("- {$service}: {$status} ({$latency}ms)");
        }

        return $mail
            ->action('View Dashboard', url('/pulse'))
            ->line('This is an automated alert from AUREX Health Monitor.');
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $color = $this->status === 'healthy' ? 'good' : 'danger';

        $fields = [];
        foreach ($this->checks as $service => $check) {
            $status = $check['status'];
            $latency = $check['latency_ms'] ?? '-';
            $error = $check['error'] ?? '';
            $fields[] = [
                'title' => $service,
                'value' => "Status: {$status}\nLatency: {$latency}ms" . ($error ? "\nError: {$error}" : ''),
                'short' => true,
            ];
        }

        return (new SlackMessage)
            ->from('AUREX Monitor', ':robot_face:')
            ->to(config('services.slack.notifications.channel'))
            ->attachment(function ($attachment) use ($color, $fields) {
                $attachment
                    ->color($color)
                    ->title("[{$this->environment}] Health Status: {$this->status}")
                    ->fields($fields)
                    ->footer("Total Latency: {$this->totalLatencyMs}ms")
                    ->timestamp(now()->timestamp);
            });
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'environment' => $this->environment,
            'status' => $this->status,
            'checks' => $this->checks,
            'total_latency_ms' => $this->totalLatencyMs,
        ];
    }
}
