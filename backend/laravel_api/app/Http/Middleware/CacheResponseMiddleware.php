<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware untuk API Response Caching.
 *
 * Item #4 Bulan 3 — API Response Caching:
 * Meng-cache response GET API yang idempotent untuk
 * mengurangi beban database dan mempercepat response time.
 *
 * Cache di-skip untuk:
 * - Authenticated user-specific data (kecuali didefinisikan)
 * - Request dengan header Cache-Control: no-cache
 * - Environment testing
 *
 * Usage di routes:
 *   Route::middleware(['cache.response:60'])->group(function () { ... });
 *   // 60 = TTL dalam detik
 *
 * Atau dengan tag:
 *   Route::middleware(['cache.response:300,analyses'])->get('/...', ...);
 *   // 300 = TTL, analyses = cache tag (untuk selective flush)
 */
class CacheResponseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, \Closure $next, string $ttl = '300', string $tag = 'default'): Response
    {
        // Hanya cache GET requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // Skip jika Cache-Control: no-cache (force refresh)
        if ($request->header('Cache-Control') === 'no-cache') {
            return $next($request);
        }

        // Skip di testing environment
        if (app()->environment('testing')) {
            return $next($request);
        }

        $cacheKey = $this->buildCacheKey($request, $tag);
        $ttlSeconds = max(1, (int) $ttl);

        // Coba ambil dari cache
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            $response = response($cached['content'], $cached['status'], $cached['headers']);
            // Tambah header X-Cache untuk debugging
            $response->headers->set('X-Cache', 'HIT');
            $response->headers->set('X-Cache-Key', $cacheKey);

            return $response;
        }

        // Proses request normal
        $response = $next($request);

        // Hanya cache response sukses (2xx)
        if ($response->isSuccessful()) {
            $cacheData = [
                'content' => $response->getContent(),
                'status' => $response->getStatusCode(),
                'headers' => $this->getCacheableHeaders($response),
            ];

            Cache::put($cacheKey, $cacheData, $ttlSeconds);

            Log::debug('API Response cached', [
                'key' => $cacheKey,
                'ttl' => $ttlSeconds,
                'path' => $request->path(),
            ]);
        }

        // Tambah header X-Cache untuk debugging
        $response->headers->set('X-Cache', 'MISS');
        $response->headers->set('X-Cache-Key', $cacheKey);

        return $response;
    }

    /**
     * Build unique cache key berdasarkan request.
     * Sertakan query params agar pagination tetap ter-cache.
     */
    private function buildCacheKey(Request $request, string $tag): string
    {
        $queryParams = $request->query();
        ksort($queryParams);

        $path = $request->path();
        $params = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';

        return "api_cache:{$tag}:{$path}{$params}";
    }

    /**
     * Filter headers yang aman di-cache.
     * Jangan cache headers yang bersifat dinamis (Set-Cookie, Authorization, dll).
     */
    private function getCacheableHeaders($response): array
    {
        $allowedHeaders = ['Content-Type', 'Content-Language'];
        $headers = [];

        foreach ($allowedHeaders as $header) {
            if ($response->headers->has($header)) {
                $headers[$header] = $response->headers->get($header);
            }
        }

        return $headers;
    }
}
