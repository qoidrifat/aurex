<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    /**
     * Detailed health check endpoint for monitoring & observability.
     *
     * Checks:
     * - Database connectivity (via raw query)
     * - Redis / Cache connectivity (via set/get)
     * - AI Service reachability (via /health endpoint)
     *
     * Returns HTTP 200 if all critical services are healthy,
     * HTTP 503 if any critical service is down.
     */
    public function __invoke(): JsonResponse
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
        } catch (\Throwable $e) {
            $healthy = false;
            $checks['database'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'latency_ms' => round((microtime(true) - $dbStart) * 1000, 2),
            ];
            Log::error('Health check: Database unhealthy', ['error' => $e->getMessage()]);
        }

        // 2. Redis / Cache check
        $cacheStart = microtime(true);
        try {
            $cacheKey = 'health:' . md5(uniqid((string) time(), true));
            Cache::store('redis')->put($cacheKey, true, 1);
            $value = Cache::store('redis')->get($cacheKey);
            Cache::store('redis')->forget($cacheKey);

            $checks['cache'] = [
                'status' => $value === true ? 'healthy' : 'degraded',
                'driver' => config('cache.default'),
                'latency_ms' => round((microtime(true) - $cacheStart) * 1000, 2),
            ];
        } catch (\Throwable $e) {
            $healthy = false;
            $checks['cache'] = [
                'status' => 'unhealthy',
                'driver' => config('cache.default'),
                'error' => $e->getMessage(),
                'latency_ms' => round((microtime(true) - $cacheStart) * 1000, 2),
            ];
            Log::error('Health check: Cache unhealthy', ['error' => $e->getMessage()]);
        }

        // 3. AI Service check (optional — non-critical if unreachable)
        $aiStart = microtime(true);
        try {
            $aiUrl = env('AI_SERVICE_URL', 'http://localhost:8001/analyze-face');
            // Robust URL parsing (handle both with and without path)
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
        } catch (\Throwable $e) {
            // AI Service might not be running — non-critical, but log it
            Log::warning('Health check: AI Service unreachable', ['error' => $e->getMessage()]);
            $checks['ai_service'] = [
                'status' => 'unreachable',
                'error' => $e->getMessage(),
                'latency_ms' => round((microtime(true) - $aiStart) * 1000, 2),
            ];
        }

        // 4. Application info
        $totalTime = round((microtime(true) - $startTime) * 1000, 2);
        $checks['app'] = [
            'name' => config('app.name'),
            'env' => config('app.env'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        $response = [
            'status' => $healthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'total_latency_ms' => $totalTime,
            'checks' => $checks,
        ];

        $httpStatus = $healthy ? 200 : 503;

        Log::info('Health check completed', [
            'status' => $response['status'],
            'latency_ms' => $totalTime,
        ]);

        return response()->json($response, $httpStatus);
    }
}
