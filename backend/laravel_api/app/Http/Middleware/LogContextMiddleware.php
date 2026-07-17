<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogContextMiddleware
{
    /**
     * Log setiap request dan response untuk observability.
     *
     * - Menambahkan request_id unik ke setiap request
     * - Mencatat method, URL, durasi, status code
     * - Mencatat user_id jika user terautentikasi
     * - Level: info untuk sukses, warning untuk 4xx, error untuk 5xx
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate unique request ID
        $requestId = (string) Str::uuid();
        $request->headers->set('X-Request-Id', $requestId);

        // Catat waktu mulai
        $startTime = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        // Hitung durasi
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        // Kumpulkan context
        $context = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        // Tambahkan user_id jika terautentikasi
        if ($request->user()) {
            $context['user_id'] = $request->user()->id;
        }

        // Tambahkan route info jika tersedia
        if ($request->route()) {
            $context['route'] = $request->route()->getName() ?? $request->route()->uri();
        }

        // Tambahkan request_id ke response header
        $response->headers->set('X-Request-Id', $requestId);

        // Log berdasarkan status code
        $statusCode = $response->getStatusCode();
        $logMessage = "{$request->method()} {$request->path()} → {$statusCode} ({$duration}ms)";

        if ($statusCode >= 500) {
            Log::error($logMessage, $context);
        } elseif ($statusCode >= 400) {
            Log::warning($logMessage, $context);
        } else {
            Log::info($logMessage, $context);
        }

        return $response;
    }
}
