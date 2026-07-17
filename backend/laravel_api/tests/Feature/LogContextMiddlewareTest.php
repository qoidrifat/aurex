<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class LogContextMiddlewareTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    // ==================== X-REQUEST-ID HEADER ====================

    public function test_response_contains_x_request_id_header()
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertHeader('X-Request-Id');
    }

    public function test_x_request_id_is_uuid_format()
    {
        $response = $this->getJson('/api/v1/health');

        $requestId = $response->headers->get('X-Request-Id');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $requestId,
            "X-Request-Id {$requestId} is not a valid UUID"
        );
    }

    public function test_each_request_gets_unique_x_request_id()
    {
        $firstResponse = $this->getJson('/api/v1/health');
        $secondResponse = $this->getJson('/api/v1/health');

        $firstId = $firstResponse->headers->get('X-Request-Id');
        $secondId = $secondResponse->headers->get('X-Request-Id');

        $this->assertNotNull($firstId);
        $this->assertNotNull($secondId);
        $this->assertNotEquals($firstId, $secondId, 'Setiap request harus memiliki X-Request-Id unik');
    }

    // ==================== MIDDLEWARE TRANSPARENCY ====================

    public function test_middleware_does_not_block_requests()
    {
        $response = $this->getJson('/api/v1/health');

        // Middleware tidak memblokir response — data JSON tetap sampai
        $this->assertNotNull($response->json());
    }

    public function test_middleware_allows_error_responses_to_pass_through()
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'not-an-email',
        ]);

        // 422 validation error harus tetap diteruskan
        $response->assertStatus(422);
        // X-Request-Id harus tetap ada meskipun response error
        $response->assertHeader('X-Request-Id');
    }

    // ==================== X-REQUEST-ID ON ALL API ROUTES ====================

    public function test_all_api_routes_have_x_request_id()
    {
        $routes = [
            ['method' => 'get', 'uri' => '/api/v1/health'],
            ['method' => 'post', 'uri' => '/api/v1/login', 'data' => ['email' => 'test@test.com', 'password' => 'password']],
        ];

        foreach ($routes as $route) {
            $method = $route['method'];
            $uri = $route['uri'];
            $data = $route['data'] ?? [];

            $response = $method === 'get'
                ? $this->getJson($uri)
                : $this->postJson($uri, $data);

            $response->assertHeader('X-Request-Id');
        }
    }

    // ==================== AUTHENTICATED USER ====================

    public function test_authenticated_request_returns_x_request_id()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/v1/user');

        $response->assertHeader('X-Request-Id');
    }

    public function test_authenticated_request_to_protected_endpoint_succeeds()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/v1/user');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_request_to_protected_endpoint_returns_401()
    {
        $response = $this->getJson('/api/v1/user');

        $response->assertStatus(401);
    }

    // ==================== X-REQUEST-ID IS PERSISTENT ====================

    public function test_x_request_id_present_on_health_check_response()
    {
        $response = $this->getJson('/api/v1/health');

        $responseId = $response->headers->get('X-Request-Id');
        $this->assertNotNull($responseId);
    }
}
