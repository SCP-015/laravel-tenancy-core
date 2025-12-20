<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\InjectBearerFromCookie;
use App\Services\ProxyTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Test untuk InjectBearerFromCookie Middleware
 * 
 * Note: IP Address yang digunakan dalam testing:
 * - 198.51.100.x: TEST-NET-2 range (RFC 5737) - IP khusus untuk testing/dokumentasi
 * - 203.0.113.x: TEST-NET-3 range (RFC 5737) - IP khusus untuk testing/dokumentasi
 * - 10.0.0.x: Private network range (RFC 1918) - Simulasi internal proxy
 * 
 * IP-IP ini tidak akan pernah di-route di internet dan aman digunakan untuk testing.
 */
class InjectBearerFromCookieTest extends TestCase
{
    private InjectBearerFromCookie $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new InjectBearerFromCookie();
    }

    /**
     * Test: Middleware melewatkan request tanpa proxy headers
     */
    public function test_middleware_passes_request_without_proxy_headers(): void
    {
        // ARRANGE
        $request = Request::create('/test', 'GET');
        $nextCalled = false;
        
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK');
        };

        // ACT
        $response = $this->middleware->handle($request, $next);

        // ASSERT
        $this->assertTrue($nextCalled);
        $this->assertEquals('OK', $response->getContent());
        $this->assertFalse($request->headers->has('Authorization'));
    }

    /**
     * Test: Middleware tidak inject token jika proxy cookie tidak ada
     */
    public function test_middleware_does_not_inject_token_without_proxy_cookie(): void
    {
        // ARRANGE
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Forwarded-For', '198.51.100.1');
        
        $next = function ($req) {
            return response('OK');
        };

        // ACT
        $response = $this->middleware->handle($request, $next);

        // ASSERT
        $this->assertFalse($request->headers->has('Authorization'));
    }

    /**
     * Test: Middleware inject token dari ProxyTokenService jika proxy valid
     */
    public function test_middleware_injects_token_from_proxy_service_when_valid(): void
    {
        // ARRANGE
        Config::set('custom.proxy_key', 'proxy_identifier');
        
        $identifier = 'test-proxy-identifier';
        $token = 'test-bearer-token-12345';
        
        // Mock ProxyTokenService
        ProxyTokenService::put($identifier, $token);
        
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Forwarded-For', '198.51.100.1, 10.0.0.1');
        $request->cookies->set('proxy_identifier', $identifier);
        
        $next = function ($req) {
            return response('OK');
        };

        // ACT
        $response = $this->middleware->handle($request, $next);

        // ASSERT
        $this->assertTrue($request->headers->has('Authorization'));
        $this->assertEquals('Bearer ' . $token, $request->headers->get('Authorization'));
        
        // Cleanup
        ProxyTokenService::delete($identifier);
    }

    /**
     * Test: Middleware set real IP dari X-Forwarded-For header
     */
    public function test_middleware_sets_real_ip_from_forwarded_for_header(): void
    {
        // ARRANGE
        Config::set('custom.proxy_key', 'proxy_identifier');
        
        $identifier = 'test-proxy-identifier';
        $token = 'test-token';
        $realIp = '203.0.113.1';
        
        ProxyTokenService::put($identifier, $token);
        
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Forwarded-For', "$realIp, 198.51.100.1");
        $request->cookies->set('proxy_identifier', $identifier);
        
        $next = function ($req) {
            return response('OK');
        };

        // ACT
        $this->middleware->handle($request, $next);

        // ASSERT
        $this->assertEquals($realIp, $request->server->get('REMOTE_ADDR'));
        $this->assertEquals($realIp, $request->server->get('HTTP_CLIENT_IP'));
        
        // Cleanup
        ProxyTokenService::delete($identifier);
    }

    /**
     * Test: Middleware bekerja dengan Via header sebagai proxy indicator
     */
    public function test_middleware_works_with_via_header_as_proxy_indicator(): void
    {
        // ARRANGE
        Config::set('custom.proxy_key', 'proxy_identifier');
        
        $identifier = 'test-proxy-identifier';
        $token = 'test-token-via';
        
        ProxyTokenService::put($identifier, $token);
        
        $request = Request::create('/test', 'GET');
        $request->headers->set('Via', '1.1 proxy-server');
        $request->cookies->set('proxy_identifier', $identifier);
        
        $next = function ($req) {
            return response('OK');
        };

        // ACT
        $this->middleware->handle($request, $next);

        // ASSERT
        $this->assertTrue($request->headers->has('Authorization'));
        $this->assertEquals('Bearer ' . $token, $request->headers->get('Authorization'));
        
        // Cleanup
        ProxyTokenService::delete($identifier);
    }

    /**
     * Test: Middleware tidak inject token jika ProxyTokenService return null
     */
    public function test_middleware_does_not_inject_token_when_service_returns_null(): void
    {
        // ARRANGE
        Config::set('custom.proxy_key', 'proxy_identifier');
        
        $identifier = 'non-existent-identifier';
        
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Forwarded-For', '198.51.100.1');
        $request->cookies->set('proxy_identifier', $identifier);
        
        $next = function ($req) {
            return response('OK');
        };

        // ACT
        $this->middleware->handle($request, $next);

        // ASSERT
        $this->assertFalse($request->headers->has('Authorization'));
    }

    /**
     * Test: Middleware handle multiple IPs di X-Forwarded-For correctly
     */
    public function test_middleware_handles_multiple_ips_in_forwarded_for(): void
    {
        // ARRANGE
        Config::set('custom.proxy_key', 'proxy_identifier');
        
        $identifier = 'test-proxy-identifier';
        $token = 'test-token';
        $clientIp = '203.0.113.50';
        $proxyChain = "$clientIp, 10.0.0.1, 10.0.0.2, 198.51.100.1";
        
        ProxyTokenService::put($identifier, $token);
        
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Forwarded-For', $proxyChain);
        $request->cookies->set('proxy_identifier', $identifier);
        
        $next = function ($req) {
            return response('OK');
        };

        // ACT
        $this->middleware->handle($request, $next);

        // ASSERT
        // Harus ambil IP pertama (client asli)
        $this->assertEquals($clientIp, $request->server->get('REMOTE_ADDR'));
        
        // Cleanup
        ProxyTokenService::delete($identifier);
    }
}
