<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCacheHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Hanya set cache untuk static assets dan landing page
        $path = $request->path();
        
        // Cache untuk static assets (images, css, js, fonts)
        if ($this->isStaticAsset($path)) {
            $response->header('Cache-Control', 'public, max-age=31536000, immutable');
            $response->header('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 31536000));
        }
        
        // Cache untuk landing page (10 menit)
        if ($path === '/' || $path === 'landing-page') {
            $response->header('Cache-Control', 'public, max-age=600');
            $response->header('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 600));
        }

        return $response;
    }

    /**
     * Check if the request is for a static asset.
     */
    private function isStaticAsset(string $path): bool
    {
        // Assets dari Vite build
        if (str_starts_with($path, 'build/')) {
            return true;
        }

        // Static images, fonts, dll di public
        $extensions = [
            'js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp',
            'ico', 'woff', 'woff2', 'ttf', 'eot', 'otf'
        ];

        foreach ($extensions as $ext) {
            if (str_ends_with($path, '.' . $ext)) {
                return true;
            }
        }

        return false;
    }
}
