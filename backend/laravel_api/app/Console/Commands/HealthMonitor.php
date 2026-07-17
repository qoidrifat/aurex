<?php

namespace App\Console\Commands;

use App\Notifications\HealthCheckAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class HealthMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health:monitor
                            {--notify : Force kirim notifikasi regardless of threshold}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periodic health check with threshold-based alerting to Slack/Email';

    /**
     * Cache key prefixes untuk tracking status kesehatan.
     */
    private const CACHE_KEY_COUNTER = 'health:consecutive_failures';
    private const CACHE_KEY_SUCCESS = 'health:consecutive_successes';
    private const CACHE_KEY_LAST_STATUS = 'health:last_status';
    private const CACHE_KEY_LAST_ALERT = 'health:last_alert_sent';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Running health monitor...');

        // Jalankan health check
        $result = $this->runHealthChecks();
        $healthy = $result['healthy'];
        $checks = $result['checks'];
        $totalLatency = $result['total_latency_ms'];

        // Tentukan status
        $status = $healthy ? 'healthy' : 'degraded';
        $env = config('app.env', 'production');

        // Update consecutive counters
        $consecutiveFailures = $this->updateConsecutiveCounters($healthy);
        $consecutiveSuccesses = Cache::get(self::CACHE_KEY_SUCCESS, 0);

        // Simpan status LAMA sebelum di-overwrite (untuk deteksi recovery)
        $oldStatus = Cache::get(self::CACHE_KEY_LAST_STATUS, 'healthy');
        Cache::put(self::CACHE_KEY_LAST_STATUS, $status, 3600);

        // Log hasil
        $this->line("   Status: {$status}");
        $this->line("   Consecutive failures: {$consecutiveFailures}");
        $this->line("   Total latency: {$totalLatency}ms");

        Log::info("Health monitor: {$status}", [
            'consecutive_failures' => $consecutiveFailures,
            'total_latency_ms' => $totalLatency,
        ]);

        // Tentukan apakah perlu mengirim notifikasi (gunakan oldStatus untuk recovery detection)
        $shouldNotify = $this->shouldSendAlert($healthy, $consecutiveFailures, $oldStatus);

        if ($shouldNotify) {
            $this->sendAlert($status, $checks, $totalLatency, $env);
        }

        // Output JSON untuk Docker healthcheck
        $this->line(json_encode([
            'status' => $status,
            'consecutive_failures' => $consecutiveFailures,
            'total_latency_ms' => $totalLatency,
            'alert_sent' => $shouldNotify,
        ]));

        return $healthy ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Run all health checks and return results.
     */
    private function runHealthChecks(): array
    {
        $startTime = microtime(true);
        $checks = [];
        $healthy = true;

        // 1. Database check
        $dbStart = microtime(true);
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1 AS health');
            $checks['database'] = [
                'status' => 'healthy',
                'latency_ms' => round((microtime(true) - $dbStart) * 1000, 2),
            ];
            $this->info('✓ Database: Connected');
        } catch (\Throwable $e) {
            $healthy = false;
            $checks['database'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'latency_ms' => round((microtime(true) - $dbStart) * 1000, 2),
            ];
            $this->error('✗ Database: ' . $e->getMessage());
            Log::error('Health monitor: Database unhealthy', ['error' => $e->getMessage()]);
        }

        // 2. Redis / Cache check
        $cacheStart = microtime(true);
        try {
            $cacheKey = 'health:monitor:' . md5(uniqid((string) time(), true));
            Cache::store('redis')->put($cacheKey, true, 1);
            $value = Cache::store('redis')->get($cacheKey);
            Cache::store('redis')->forget($cacheKey);

            $checks['cache'] = [
                'status' => $value === true ? 'healthy' : 'degraded',
                'driver' => config('cache.default'),
                'latency_ms' => round((microtime(true) - $cacheStart) * 1000, 2),
            ];
            $this->info('✓ Cache (Redis): Working');
        } catch (\Throwable $e) {
            $healthy = false;
            $checks['cache'] = [
                'status' => 'unhealthy',
                'driver' => config('cache.default'),
                'error' => $e->getMessage(),
                'latency_ms' => round((microtime(true) - $cacheStart) * 1000, 2),
            ];
            $this->error('✗ Cache (Redis): ' . $e->getMessage());
        }

        // 3. AI Service check (non-critical)
        $aiStart = microtime(true);
        try {
            $aiUrl = env('AI_SERVICE_URL', 'http://localhost:8001/analyze-face');
            $parsed = parse_url($aiUrl);
            $baseUrl = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? 'localhost');
            if (isset($parsed['port'])) {
                $baseUrl .= ':' . $parsed['port'];
            }
            $healthUrl = $baseUrl . '/health';
            $aiResponse = Http::timeout(5)->get($healthUrl);

            $checks['ai_service'] = [
                'status' => $aiResponse->successful() ? 'healthy' : 'unhealthy',
                'status_code' => $aiResponse->status(),
                'latency_ms' => round((microtime(true) - $aiStart) * 1000, 2),
            ];
            if ($aiResponse->successful()) {
                $this->info('✓ AI Service: Healthy');
            } else {
                $this->warn('~ AI Service: Unhealthy (status: ' . $aiResponse->status() . ')');
            }
        } catch (\Throwable $e) {
            $checks['ai_service'] = [
                'status' => 'unreachable',
                'error' => $e->getMessage(),
                'latency_ms' => round((microtime(true) - $aiStart) * 1000, 2),
            ];
            $this->warn('~ AI Service: Unreachable (' . $e->getMessage() . ')');
        }

        // 4. Check latency thresholds
        foreach ($checks as $service => $check) {
            if (isset($check['latency_ms'])) {
                $latencyWarning = (int) config('monitoring.health_check.latency_warning', 500);
                $latencyCritical = (int) config('monitoring.health_check.latency_critical', 1000);

                if ($check['latency_ms'] > $latencyCritical) {
                    Log::warning("Health monitor: {$service} latency critical", [
                        'latency_ms' => $check['latency_ms'],
                        'threshold' => $latencyCritical,
                    ]);
                } elseif ($check['latency_ms'] > $latencyWarning) {
                    Log::info("Health monitor: {$service} latency warning", [
                        'latency_ms' => $check['latency_ms'],
                        'threshold' => $latencyWarning,
                    ]);
                }
            }
        }

        return [
            'healthy' => $healthy,
            'checks' => $checks,
            'total_latency_ms' => round((microtime(true) - $startTime) * 1000, 2),
        ];
    }

    /**
     * Update consecutive failure/success counters in cache.
     */
    private function updateConsecutiveCounters(bool $healthy): int
    {
        $failureThreshold = (int) config('monitoring.health_check.failure_threshold', 3);
        $recoveryThreshold = (int) config('monitoring.health_check.recovery_threshold', 2);

        if ($healthy) {
            // Increment consecutive successes
            $successes = (int) Cache::get(self::CACHE_KEY_SUCCESS, 0) + 1;
            Cache::put(self::CACHE_KEY_SUCCESS, $successes, 3600);

            // Reset consecutive failures
            Cache::forget(self::CACHE_KEY_COUNTER);

            // Jika sudah melewati recovery threshold, notifikasi "recovered"
            if ($successes >= $recoveryThreshold) {
                // Biarkan shouldSendAlert handle ini
            }

            return 0;
        }

        // Increment consecutive failures
        $failures = (int) Cache::get(self::CACHE_KEY_COUNTER, 0) + 1;
        Cache::put(self::CACHE_KEY_COUNTER, $failures, 3600);

        // Reset consecutive successes
        Cache::forget(self::CACHE_KEY_SUCCESS);

        return $failures;
    }

    /**
     * Determine whether an alert should be sent based on thresholds.
     */
    private function shouldSendAlert(bool $healthy, int $consecutiveFailures, string $oldStatus = 'healthy'): bool
    {
        $failureThreshold = (int) config('monitoring.health_check.failure_threshold', 3);
        $recoveryThreshold = (int) config('monitoring.health_check.recovery_threshold', 2);

        // Force notify flag
        if ($this->option('notify')) {
            return true;
        }

        if (!$healthy) {
            // Kirim alert jika sudah mencapai failure threshold
            if ($consecutiveFailures >= $failureThreshold) {
                // Cek apakah sudah pernah dikirim untuk failure streak ini
                $lastAlert = Cache::get(self::CACHE_KEY_LAST_ALERT, 0);
                return $lastAlert < $consecutiveFailures;
            }
            return false;
        }

        // Jika system recovered, kirim notifikasi recovery
        // Gunakan $oldStatus dari parameter (bukan baca ulang dari cache,
        // karena status sudah di-update sebelum method ini dipanggil)
        $successes = (int) Cache::get(self::CACHE_KEY_SUCCESS, 0);
        if ($successes >= $recoveryThreshold) {
            return $oldStatus !== 'healthy';
        }

        return false;
    }

    /**
     * Send alert notification via configured channels.
     */
    private function sendAlert(string $status, array $checks, float $totalLatency, string $env): void
    {
        try {
            $recipients = [];

            // Kirim via Email jika dikonfigurasi
            $mailRecipients = config('monitoring.health_check.mail_recipients', []);
            if (!empty($mailRecipients) && config('monitoring.health_check.alert_channels.mail')) {
                foreach ($mailRecipients as $email) {
                    $email = trim($email);
                    if (!empty($email)) {
                        $recipients[] = new \Illuminate\Notifications\AnonymousNotifiable();
                        $recipients[count($recipients) - 1]->route('mail', $email);
                    }
                }
            }

            // Kirim via Slack jika dikonfigurasi
            if (config('monitoring.health_check.alert_channels.slack')) {
                $slackChannel = config('services.slack.notifications.channel');
                if ($slackChannel) {
                    $recipients[] = new \Illuminate\Notifications\AnonymousNotifiable();
                    $recipients[count($recipients) - 1]->route('slack', $slackChannel);
                }
            }

            // Jika tidak ada channel terkonfigurasi, log saja
            if (empty($recipients)) {
                Log::info('Health monitor: No alert channels configured. Set HEALTH_ALERT_SLACK_ENABLED or HEALTH_ALERT_MAIL_ENABLED.');
                return;
            }

            // Kirim notifikasi ke semua recipients
            foreach ($recipients as $notifiable) {
                $notifiable->notify(new HealthCheckAlert(
                    status: $status,
                    checks: $checks,
                    totalLatencyMs: $totalLatency,
                    environment: $env,
                ));
            }

            // Tandai alert sudah dikirim untuk mencegah spam
            $failures = (int) Cache::get(self::CACHE_KEY_COUNTER, 0);
            Cache::put(self::CACHE_KEY_LAST_ALERT, $failures, 3600);

            $this->info('✓ Alert sent successfully');
            Log::info("Health monitor: Alert sent for status={$status}");

        } catch (\Throwable $e) {
            $this->error('✗ Failed to send alert: ' . $e->getMessage());
            Log::error('Health monitor: Failed to send alert', ['error' => $e->getMessage()]);
        }
    }
}
