<?php

namespace App\Http\Middleware;

use App\Services\ProxyTokenService;
use Closure;
use Illuminate\Http\Request;

class InjectBearerFromCookie
{
    /**
     * Handle an incoming request.
     * Inject bearer token from cookie if it exists.
     */
    public function handle(Request $request, Closure $next)
    {
        $isProxy = $request->hasHeader('X-Forwarded-For') || $request->hasHeader('Via');
        $identifier = $request->cookie(config('custom.proxy_key'));

        if ($isProxy && $identifier) {
            // Ambil IP asli dari X-Forwarded-For (ambil yang paling depan)
            $forwardedFor = $request->header('X-Forwarded-For');
            if ($forwardedFor) {
                $realIp = trim(explode(',', $forwardedFor)[0]);
                $request->server->set('REMOTE_ADDR', $realIp);
                $request->server->set('HTTP_CLIENT_IP', $realIp);
            }

            // Ambil token dari storage proxy
            $token = ProxyTokenService::get($identifier);
            if ($token) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
        }

        return $next($request);
    }
}
