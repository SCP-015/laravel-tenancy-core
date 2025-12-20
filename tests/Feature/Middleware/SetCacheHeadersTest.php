<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\SetCacheHeaders;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Test untuk SetCacheHeaders Middleware
 *
 * Middleware ini menangani:
 * - Set cache header untuk static assets (1 tahun)
 * - Set cache header untuk landing page (10 menit)
 * - Tidak set cache untuk halaman lain
 */
class SetCacheHeadersTest extends TestCase
{
    private SetCacheHeaders $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SetCacheHeaders();
    }

    private function createNextCallback(): callable
    {
        return static function () {
            return response('OK');
        };
    }

    private function assertStaticAssetCache($response): void
    {
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('max-age=31536000', $cacheControl);
        $this->assertStringContainsString('immutable', $cacheControl);
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertNotNull($response->headers->get('Expires'));
    }

    private function assertLandingPageCache($response): void
    {
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('max-age=600', $cacheControl);
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertNotNull($response->headers->get('Expires'));
    }

    private function assertNoCacheHeaderSet($response): void
    {
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringNotContainsString('max-age=31536000', $cacheControl);
        $this->assertStringNotContainsString('max-age=600', $cacheControl);
        $this->assertNull($response->headers->get('Expires'));
    }

    // ==================== STATIC ASSETS TESTS ====================

    /**
     * Test: Cache header untuk build assets (Vite)
     */
    public function test_cache_header_for_build_assets(): void
    {
        $request = Request::create('/build/assets/app.js', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Cache header untuk build assets dengan path panjang
     */
    public function test_cache_header_for_build_assets_with_long_path(): void
    {
        $request = Request::create('/build/assets/app-abc123def456.js', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Cache header untuk JavaScript files
     */
    public function test_cache_header_for_javascript_files(): void
    {
        $request = Request::create('/public/app.js', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Cache header untuk CSS files
     */
    public function test_cache_header_for_css_files(): void
    {
        $request = Request::create('/public/style.css', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Cache header untuk PNG images
     */
    public function test_cache_header_for_png_images(): void
    {
        $request = Request::create('/public/images/logo.png', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Cache header untuk JPG images
     */
    public function test_cache_header_for_jpg_images(): void
    {
        $request = Request::create('/public/images/banner.jpg', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Cache header untuk JPEG images
     */
    public function test_cache_header_for_jpeg_images(): void
    {
        $request = Request::create('/public/images/photo.jpeg', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Cache header untuk GIF images
     */
    public function test_cache_header_for_gif_images(): void
    {
        $request = Request::create('/public/images/animation.gif', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Cache header untuk SVG images
     */
    public function test_cache_header_for_svg_images(): void
    {
        $request = Request::create('/public/images/icon.svg', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Cache header untuk WebP images
     */
    public function test_cache_header_for_webp_images(): void
    {
        $request = Request::create('/public/images/photo.webp', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Cache header untuk ICO files
     */
    public function test_cache_header_for_ico_files(): void
    {
        $request = Request::create('/favicon-nusahire.ico', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Cache header untuk WOFF fonts
     */
    public function test_cache_header_for_woff_fonts(): void
    {
        $request = Request::create('/public/fonts/roboto.woff', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Cache header untuk WOFF2 fonts
     */
    public function test_cache_header_for_woff2_fonts(): void
    {
        $request = Request::create('/public/fonts/roboto.woff2', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Cache header untuk TTF fonts
     */
    public function test_cache_header_for_ttf_fonts(): void
    {
        $request = Request::create('/public/fonts/roboto.ttf', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Cache header untuk EOT fonts
     */
    public function test_cache_header_for_eot_fonts(): void
    {
        $request = Request::create('/public/fonts/roboto.eot', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Cache header untuk OTF fonts
     */
    public function test_cache_header_for_otf_fonts(): void
    {
        $request = Request::create('/public/fonts/roboto.otf', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Expires header untuk static assets (1 tahun)
     */
    public function test_expires_header_for_static_assets_one_year(): void
    {
        $request = Request::create('/public/app.js', 'GET');
        $beforeTime = time();

        $response = $this->middleware->handle($request, $this->createNextCallback());
        $afterTime = time();

        $expiresHeader = $response->headers->get('Expires');
        $this->assertNotNull($expiresHeader);

        $expiresTime = strtotime($expiresHeader);
        $expectedTime = $beforeTime + 31536000;

        $this->assertGreaterThanOrEqual($expectedTime - 2, $expiresTime);
        $this->assertLessThanOrEqual($afterTime + 31536000 + 2, $expiresTime);
    }

    // ==================== LANDING PAGE TESTS ====================

    /**
     * Test: Cache header untuk root path (/)
     */
    public function test_cache_header_for_root_path(): void
    {
        $request = Request::create('/', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertLandingPageCache($response);
    }

    /**
     * Test: Cache header untuk landing-page path
     */
    public function test_cache_header_for_landing_page_path(): void
    {
        $request = Request::create('/landing-page', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertLandingPageCache($response);
    }

    /**
     * Test: Expires header untuk landing page (10 menit)
     */
    public function test_expires_header_for_landing_page_ten_minutes(): void
    {
        $request = Request::create('/', 'GET');
        $beforeTime = time();

        $response = $this->middleware->handle($request, $this->createNextCallback());
        $afterTime = time();

        $expiresHeader = $response->headers->get('Expires');
        $this->assertNotNull($expiresHeader);

        $expiresTime = strtotime($expiresHeader);
        $expectedTime = $beforeTime + 600;

        $this->assertGreaterThanOrEqual($expectedTime - 2, $expiresTime);
        $this->assertLessThanOrEqual($afterTime + 600 + 2, $expiresTime);
    }

    // ==================== NO CACHE TESTS ====================

    /**
     * Test: Tidak ada cache header untuk halaman biasa
     */
    public function test_no_cache_header_for_regular_pages(): void
    {
        $request = Request::create('/dashboard', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertNoCacheHeaderSet($response);
    }

    /**
     * Test: Tidak ada cache header untuk API endpoints
     */
    public function test_no_cache_header_for_api_endpoints(): void
    {
        $request = Request::create('/api/users', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertNoCacheHeaderSet($response);
    }

    /**
     * Test: Tidak ada cache header untuk admin pages
     */
    public function test_no_cache_header_for_admin_pages(): void
    {
        $request = Request::create('/admin/dashboard', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertNoCacheHeaderSet($response);
    }

    /**
     * Test: Tidak ada cache header untuk user profile pages
     */
    public function test_no_cache_header_for_user_profile_pages(): void
    {
        $request = Request::create('/profile/user-123', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertNoCacheHeaderSet($response);
    }

    // ==================== RESPONSE HANDLING TESTS ====================

    /**
     * Test: Middleware returns response dari next callback
     */
    public function test_middleware_returns_response_from_next(): void
    {
        $request = Request::create('/public/app.js', 'GET');

        $next = static function () {
            return response('Test Response', 201);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals('Test Response', $response->getContent());
        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * Test: Middleware preserves response headers
     */
    public function test_middleware_preserves_response_headers(): void
    {
        $request = Request::create('/public/app.js', 'GET');

        $next = static function () {
            return response('OK')
                ->header('X-Custom-Header', 'custom-value')
                ->header('Content-Type', 'application/json');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals('custom-value', $response->headers->get('X-Custom-Header'));
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    /**
     * Test: Middleware works with different HTTP methods
     */
    public function test_middleware_works_with_different_http_methods(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

        foreach ($methods as $method) {
            $request = Request::create('/public/app.js', $method);
            $response = $this->middleware->handle($request, $this->createNextCallback());
            $this->assertStaticAssetCache($response);
        }
    }

    // ==================== EDGE CASES TESTS ====================

    /**
     * Test: Case-sensitive file extensions
     */
    public function test_case_sensitive_file_extensions(): void
    {
        $request = Request::create('/public/app.JS', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertNoCacheHeaderSet($response);
    }

    /**
     * Test: File with multiple extensions
     */
    public function test_file_with_multiple_extensions(): void
    {
        $request = Request::create('/public/app.min.js', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Path with query parameters
     */
    public function test_path_with_query_parameters(): void
    {
        $request = Request::create('/public/app.js?v=1.0', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Path with fragments
     */
    public function test_path_with_fragments(): void
    {
        $request = Request::create('/public/app.js#section', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Nested build directory paths
     */
    public function test_nested_build_directory_paths(): void
    {
        $request = Request::create('/build/assets/vendor/app.js', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Deep nested asset paths
     */
    public function test_deep_nested_asset_paths(): void
    {
        $request = Request::create('/public/assets/images/icons/logo.png', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Unknown file extension
     */
    public function test_unknown_file_extension(): void
    {
        $request = Request::create('/public/file.unknown', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertNoCacheHeaderSet($response);
    }

    /**
     * Test: File without extension
     */
    public function test_file_without_extension(): void
    {
        $request = Request::create('/public/file', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertNoCacheHeaderSet($response);
    }

    /**
     * Test: Path that starts with build but is not build directory
     */
    public function test_path_starts_with_build_but_not_build_directory(): void
    {
        $request = Request::create('/builder/app.js', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: Multiple cache rules don't conflict (static asset + landing page)
     */
    public function test_multiple_cache_rules_dont_conflict(): void
    {
        $request = Request::create('/landing-page.js', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());
        $this->assertStaticAssetCache($response);
    }

    /**
     * Test: isStaticAsset method covers all extensions
     */
    public function test_all_supported_extensions(): void
    {
        $extensions = ['js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'otf'];

        foreach ($extensions as $ext) {
            $request = Request::create("/public/file.{$ext}", 'GET');
            $response = $this->middleware->handle($request, $this->createNextCallback());
            $this->assertStaticAssetCache($response);
        }
    }

    /**
     * Test: Build directory detection
     */
    public function test_build_directory_detection(): void
    {
        $paths = [
            '/build/app.js',
            '/build/assets/app.js',
            '/build/assets/vendor/app.js',
            '/build/css/style.css',
        ];

        foreach ($paths as $path) {
            $request = Request::create($path, 'GET');
            $response = $this->middleware->handle($request, $this->createNextCallback());
            $this->assertStaticAssetCache($response);
        }
    }

    /**
     * Test: Landing page path variations
     */
    public function test_landing_page_path_variations(): void
    {
        $paths = ['/', '/landing-page'];

        foreach ($paths as $path) {
            $request = Request::create($path, 'GET');
            $response = $this->middleware->handle($request, $this->createNextCallback());
            $this->assertLandingPageCache($response);
        }
    }

    /**
     * Test: Cache-Control header format for static assets
     */
    public function test_cache_control_header_format_for_static_assets(): void
    {
        $request = Request::create('/public/app.js', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertNotNull($cacheControl);
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=31536000', $cacheControl);
        $this->assertStringContainsString('immutable', $cacheControl);
    }

    /**
     * Test: Cache-Control header format for landing page
     */
    public function test_cache_control_header_format_for_landing_page(): void
    {
        $request = Request::create('/', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertNotNull($cacheControl);
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=600', $cacheControl);
    }

    /**
     * Test: Expires header format
     */
    public function test_expires_header_format(): void
    {
        $request = Request::create('/public/app.js', 'GET');
        $response = $this->middleware->handle($request, $this->createNextCallback());

        $expires = $response->headers->get('Expires');
        $this->assertNotNull($expires);
        $this->assertStringContainsString('GMT', $expires);
        $this->assertMatchesRegularExpression('/\d{1,2}\s\w{3}\s\d{4}\s\d{2}:\d{2}:\d{2}\sGMT/', $expires);
    }
}
