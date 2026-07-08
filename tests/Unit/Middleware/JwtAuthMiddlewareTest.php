<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\JwtAuthMiddleware;
use Firebase\JWT\JWT;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class JwtAuthMiddlewareTest extends TestCase
{
    private JwtAuthMiddleware $middleware;
    private string $secret = 'test-jwt-secret-change-me-please';

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new JwtAuthMiddleware();
        putenv("JWT_SECRET={$this->secret}");
        $_ENV['JWT_SECRET'] = $this->secret;
    }

    private function makeToken(array $payload = []): string
    {
        $base = [
            'sub' => 1,
            'email' => 'admin@pointsmall.com',
            'roles' => ['admin'],
            'iat' => time(),
            'exp' => time() + 900,
        ];
        return JWT::encode(array_merge($base, $payload), $this->secret, 'HS256');
    }

    private function makeRequest(string $method, string $uri, ?string $authHeader = null): Request
    {
        $request = Request::create($uri, $method);
        if ($authHeader !== null) {
            $request->headers->set('Authorization', $authHeader);
        }
        return $request;
    }

    // AC-01: valid token passes through, next() called
    public function test_valid_bearer_token_passes_through(): void
    {
        $token = $this->makeToken();
        $request = $this->makeRequest('GET', '/api/products', "Bearer {$token}");

        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return new JsonResponse(['ok' => true]);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertTrue($nextCalled);
        $this->assertEquals(200, $response->getStatusCode());
    }

    // AC-01: decoded payload attached to request attributes
    public function test_valid_token_attaches_payload_to_request(): void
    {
        $token = $this->makeToken(['sub' => 42, 'email' => 'alice@test.com']);
        $request = $this->makeRequest('GET', '/api/products', "Bearer {$token}");

        $capturedPayload = null;
        $next = function ($req) use (&$capturedPayload) {
            $capturedPayload = $req->attributes->get('auth_user');
            return new JsonResponse([]);
        };

        $this->middleware->handle($request, $next);

        $this->assertNotNull($capturedPayload);
        $this->assertEquals(42, $capturedPayload->sub);
        $this->assertEquals('alice@test.com', $capturedPayload->email);
    }

    // AC-02: missing Authorization header → 401 shop-4001
    public function test_missing_authorization_header_returns_401(): void
    {
        $request = $this->makeRequest('GET', '/api/products');
        $next = fn ($req) => new JsonResponse([]);

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertEquals('shop-4001', $body['code']);
        $this->assertEquals('Unauthorized', $body['message']);
        $this->assertNull($body['data']);
    }

    // AC-03: tampered/invalid token → 401
    public function test_invalid_token_returns_401(): void
    {
        $request = $this->makeRequest('GET', '/api/products', 'Bearer not.a.valid.jwt');
        $response = $this->middleware->handle($request, fn ($r) => new JsonResponse([]));

        $this->assertEquals(401, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertEquals('shop-4001', $body['code']);
    }

    // AC-03: expired token → 401
    public function test_expired_token_returns_401(): void
    {
        $expiredToken = $this->makeToken(['exp' => time() - 100]);
        $request = $this->makeRequest('GET', '/api/products', "Bearer {$expiredToken}");
        $response = $this->middleware->handle($request, fn ($r) => new JsonResponse([]));

        $this->assertEquals(401, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertEquals('shop-4001', $body['code']);
    }

    // AC-05: wrong secret → 401
    public function test_token_signed_with_wrong_secret_returns_401(): void
    {
        $token = JWT::encode(
            ['sub' => 1, 'email' => 'a@b.com', 'roles' => ['admin'], 'exp' => time() + 900],
            'wrong-secret-that-is-long-enough-here',
            'HS256',
        );
        $request = $this->makeRequest('GET', '/api/products', "Bearer {$token}");
        $response = $this->middleware->handle($request, fn ($r) => new JsonResponse([]));

        $this->assertEquals(401, $response->getStatusCode());
    }
}
