<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class HealthCheckAlert extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $status,
        public array $checks,
        public float $totalLatencyMs,
        public string $environment,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        // Kirim notifikasi berdasarkan konfigurasi environment
        if (config('monitoring.alert_channels.slack')) {
            $channels[] = 'slack';
        }
        if (config('monitoring.alert_channels.mail')) {
            $channels[] = 'mail';
        }

        return $channels ?: ['log'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $isHealthy = $this->status === 'healthy';
        $subject = $isHealthy
            ? "✅ [{$this->environment}] AUREX Health Check: RECOVERED"
            : "🚨 [{$this->environment}] AUREX Health Check: {$this->status}";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting($isHealthy ? '✅ Sistem pulih!' : '🚨 Ada masalah dengan sistem!')
            ->line("**Environment:** {$this->environment}")
            ->line("**Status:** {$this->status}")
            ->line("**Total Latency:** {$this->totalLatencyMs}ms");

        // Detail per-service check
        foreach ($this->checks as $service => $result) {
            if ($service === 'app') {
                continue;
            }
            $icon = $result['status'] === 'healthy' ? '✅' : '❌';
            $latency = isset($result['latency_ms']) ? " ({$result['latency_ms']}ms)" : '';
            $error = isset($result['error']) ? " — Error: {$result['error']}" : '';
            $mail->line("{$icon} **{$service}:** {$result['status']}{$latency}{$error}");
        }

        $mail->action('Lihat Dashboard', url('/up'))
            ->line('Notifikasi ini dikirim otomatis oleh AUREX Health Monitor.')
            ->lineIf(!$isHealthy, '⏱ Akan dicek ulang dalam 1 menit.');

        return $mail;
    }

    /**
     * Get the Slack representation of the notification.
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        $isHealthy = $this->status === 'healthy';

        $slack = (new SlackMessage)
            ->from('AUREX Monitor', ':robot_face:');

        if ($isHealthy) {
            $slack->success()
                ->content("✅ *[{$this->environment}]* AUREX system has RECOVERED — all services healthy.");
        } else {
            $slack->error()
                ->content("🚨 *[{$this->environment}]* AUREX Health Check: *{$this->status}*");
        }

        // Tambahkan attachment per-service check
        $attachmentFields = [];
        foreach ($this->checks as $service => $result) {
            if ($service === 'app') {
                continue;
            }
            $icon = $result['status'] === 'healthy' ? '✅' : '❌';
            $latency = isset($result['latency_ms']) ? " ({$result['latency_ms']}ms)" : '';
            $error = isset($result['error']) ? " — `{$result['error']}`" : '';
            $attachmentFields[] = [
                'title' => "{$icon} {$service}",
                'value' => "Status: {$result['status']}{$latency}{$error}",
                'short' => true,
            ];
        }

        $slack->attachment(function ($attachment) use ($attachmentFields, $isHealthy) {
            $attachment->fields($attachmentFields)
                ->timestamp(now());

            if (!$isHealthy) {
                $attachment->actions([
                    'type' => 'button',
                    'text' => '🔍 Buka Dashboard',
                    'url' => url('/up'),
                    'style' => 'danger',
                ]);
            }
        });

        return $slack;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'status' => $this->status,
            'checks' => $this->checks,
            'total_latency_ms' => $this->totalLatencyMs,
            'environment' => $this->environment,
        ];
    }
}
