<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HealthControllerTest extends TestCase
{
    // ==================== ENDPOINT RESPONSE STRUCTURE ====================

    public function test_health_endpoint_returns_expected_json_structure()
    {
        Http::fake(['*/health' => Http::response(['status' => 'healthy'], 200)]);

        $response = $this->getJson('/api/v1/health');

        $response->assertJsonStructure([
            'status',
            'timestamp',
            'total_latency_ms',
            'checks' => [
                'database' => ['status'],
                'cache' => ['status', 'driver'],
                'ai_service' => ['status'],
                'app' => ['name', 'env', 'php_version', 'laravel_version'],
            ],
        ]);
    }

    public function test_health_endpoint_returns_json_content_type()
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertHeader('Content-Type', 'application/json');
    }

    // ==================== DATABASE HEALTH ====================

    public function test_database_is_healthy_when_connected()
    {
        Http::fake(['*/health' => Http::response(['status' => 'healthy'], 200)]);

        $response = $this->getJson('/api/v1/health');

        $response->assertJsonPath('checks.database.status', 'healthy');
        $this->assertGreaterThan(
            0,
            $response->json('checks.database.latency_ms'),
            'Database latency must be positive'
        );
    }

    // ==================== CACHE / REDIS HEALTH ====================

    public function test_cache_is_unhealthy_when_redis_store_not_available()
    {
        // Di test environment, CACHE_STORE=array dan redis store tidak ada
        Http::fake(['*/health' => Http::response(['status' => 'healthy'], 200)]);

        $response = $this->getJson('/api/v1/health');

        $response->assertJsonPath('checks.cache.status', 'unhealthy');
        $this->assertNotEmpty($response->json('checks.cache.error'));
    }

    public function test_cache_check_reports_correct_driver()
    {
        Http::fake(['*/health' => Http::response(['status' => 'healthy'], 200)]);

        $response = $this->getJson('/api/v1/health');

        // Driver harus 'array' sesuai phpunit.xml
        $response->assertJsonPath('checks.cache.driver', 'array');
    }

    // ==================== AI SERVICE HEALTH ====================

    public function test_ai_service_is_healthy_when_reachable()
    {
        Http::fake([
            '*/health' => Http::response(['status' => 'healthy'], 200),
        ]);

        $response = $this->getJson('/api/v1/health');

        $response->assertJsonPath('checks.ai_service.status', 'healthy');
        $response->assertJsonPath('checks.ai_service.status_code', 200);
    }

    public function test_ai_service_is_unreachable_when_connection_fails()
    {
        // Mock koneksi gagal menggunakan closure yang throw exception
        Http::fake([
            '*/health' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
            },
        ]);

        $response = $this->getJson('/api/v1/health');

        $response->assertJsonPath('checks.ai_service.status', 'unreachable');
    }

    public function test_ai_service_is_unhealthy_when_returns_error()
    {
        Http::fake([
            '*/health' => Http::response(['status' => 'error'], 503),
        ]);

        $response = $this->getJson('/api/v1/health');

        $response->assertJsonPath('checks.ai_service.status', 'unhealthy');
        $response->assertJsonPath('checks.ai_service.status_code', 503);
    }

    // ==================== OVERALL STATUS ====================

    public function test_overall_status_is_degraded_when_cache_fails()
    {
        // Cache akan gagal karena redis store tidak ada di test environment
        // AI Service akan sehat karena di-mock
        Http::fake(['*/health' => Http::response(['status' => 'healthy'], 200)]);

        $response = $this->getJson('/api/v1/health');

        // Cache dianggap critical, jadi status = degraded
        $response->assertJsonPath('status', 'degraded');
    }

    public function test_overall_status_returns_503_when_degraded()
    {
        Http::fake(['*/health' => Http::response(['status' => 'healthy'], 200)]);

        $response = $this->getJson('/api/v1/health');

        // Cache gagal → healthy=false → status code 503
        $response->assertStatus(503);
    }

    // ==================== TIMESTAMP FORMAT ====================

    public function test_timestamp_is_in_iso8601_format()
    {
        Http::fake(['*/health' => Http::response(['status' => 'healthy'], 200)]);

        $response = $this->getJson('/api/v1/health');

        $timestamp = $response->json('timestamp');
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $timestamp,
            "Timestamp {$timestamp} is not in ISO 8601 format"
        );
    }

    // ==================== APPLICATION INFO ====================

    public function test_app_info_contains_correct_data()
    {
        Http::fake(['*/health' => Http::response(['status' => 'healthy'], 200)]);

        $response = $this->getJson('/api/v1/health');

        $this->assertEquals('testing', $response->json('checks.app.env'));
        $this->assertEquals(PHP_VERSION, $response->json('checks.app.php_version'));
        $response->assertJsonStructure([
            'checks' => ['app' => ['laravel_version']],
        ]);
    }

    // ==================== LATENCY METRICS ====================

    public function test_total_latency_is_positive()
    {
        Http::fake(['*/health' => Http::response(['status' => 'healthy'], 200)]);

        $response = $this->getJson('/api/v1/health');

        $this->assertGreaterThan(0, $response->json('total_latency_ms'));
    }

    public function test_each_check_has_latency_metric()
    {
        Http::fake(['*/health' => Http::response(['status' => 'healthy'], 200)]);

        $response = $this->getJson('/api/v1/health');

        $checks = $response->json('checks');
        foreach (['database', 'cache', 'ai_service'] as $service) {
            $this->assertArrayHasKey('latency_ms', $checks[$service]);
        }
    }

    // ==================== ERROR DETAILS ====================

    public function test_error_field_present_when_service_unhealthy()
    {
        Http::fake(['*/health' => Http::response(['status' => 'healthy'], 200)]);

        $response = $this->getJson('/api/v1/health');

        // Cache gagal → harus ada field 'error'
        $checks = $response->json('checks');
        $this->assertArrayHasKey('error', $checks['cache']);
        $this->assertNotEmpty($checks['cache']['error']);
    }

    // ==================== PUBLIC ACCESS ====================

    public function test_health_endpoint_is_public_without_auth()
    {
        Http::fake(['*/health' => Http::response(['status' => 'healthy'], 200)]);

        $response = $this->getJson('/api/v1/health');

        // Health endpoint harus bisa diakses tanpa token (TIDAK 401)
        $this->assertNotEquals(401, $response->getStatusCode());
    }
}
