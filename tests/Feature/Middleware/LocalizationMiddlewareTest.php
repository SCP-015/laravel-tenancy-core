<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\LocalizationMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

/**
 * Test untuk LocalizationMiddleware
 * 
 * Middleware ini menangani:
 * - Set locale berdasarkan header X-Localization
 * - Default locale jika header tidak ada
 */
class LocalizationMiddlewareTest extends TestCase
{
    private LocalizationMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new LocalizationMiddleware();
    }

    /**
     * Test: Middleware set locale ke 'en' dari header
     */
    public function test_middleware_sets_locale_to_en_from_header(): void
    {
        // ARRANGE
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Localization', 'en');
        
        $next = function ($req) {
            return response('OK');
        };

        // ACT
        $this->middleware->handle($request, $next);

        // ASSERT
        $this->assertEquals('en', App::getLocale());
    }

    /**
     * Test: Middleware set locale ke 'id' dari header
     */
    public function test_middleware_sets_locale_to_id_from_header(): void
    {
        // ARRANGE
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Localization', 'id');
        
        $next = function ($req) {
            return response('OK');
        };

        // ACT
        $this->middleware->handle($request, $next);

        // ASSERT
        $this->assertEquals('id', App::getLocale());
    }

    /**
     * Test: Middleware default ke 'id' jika header tidak ada
     */
    public function test_middleware_defaults_to_id_when_header_missing(): void
    {
        // ARRANGE
        $request = Request::create('/test', 'GET');
        // No X-Localization header
        
        $next = function ($req) {
            return response('OK');
        };

        // ACT
        $this->middleware->handle($request, $next);

        // ASSERT
        $this->assertEquals('id', App::getLocale());
    }

    /**
     * Test: Middleware convert header ke lowercase
     */
    public function test_middleware_converts_header_to_lowercase(): void
    {
        // ARRANGE
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Localization', 'EN'); // uppercase
        
        $next = function ($req) {
            return response('OK');
        };

        // ACT
        $this->middleware->handle($request, $next);

        // ASSERT
        $this->assertEquals('en', App::getLocale());
    }

    /**
     * Test: Middleware tetap set locale meskipun invalid (Laravel handles fallback)
     */
    public function test_middleware_sets_locale_even_for_invalid_value(): void
    {
        // ARRANGE
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Localization', 'xx'); // invalid locale
        
        $next = function ($req) {
            return response('OK');
        };

        // ACT
        $this->middleware->handle($request, $next);

        // ASSERT - Laravel akan set ke 'xx' tapi fallback ke default saat translate
        $this->assertEquals('xx', App::getLocale());
    }

    /**
     * Test: Middleware calls next dengan request yang sama
     */
    public function test_middleware_calls_next_with_request(): void
    {
        // ARRANGE
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Localization', 'en');
        
        $nextCalled = false;
        $passedRequest = null;
        
        $next = function ($req) use (&$nextCalled, &$passedRequest) {
            $nextCalled = true;
            $passedRequest = $req;
            return response('OK');
        };

        // ACT
        $response = $this->middleware->handle($request, $next);

        // ASSERT
        $this->assertTrue($nextCalled);
        $this->assertSame($request, $passedRequest);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test: Middleware dengan berbagai locale variants
     */
    public function test_middleware_handles_various_locale_formats(): void
    {
        $testCases = [
            'en' => 'en',
            'EN' => 'en',
            'En' => 'en',
            'id' => 'id',
            'ID' => 'id',
            'en-US' => 'en-us',
            'pt-BR' => 'pt-br',
        ];

        foreach ($testCases as $input => $expected) {
            // ARRANGE
            $request = Request::create('/test', 'GET');
            $request->headers->set('X-Localization', $input);
            
            $next = function ($req) {
                return response('OK');
            };

            // ACT
            $this->middleware->handle($request, $next);

            // ASSERT
            $this->assertEquals($expected, App::getLocale(), "Failed for input: {$input}");
        }
    }
}
