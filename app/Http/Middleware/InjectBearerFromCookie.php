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
        $identifier = $request->cookie(config('custom.proxy_key'));

        if ($identifier) {
            // Ambil IP asli dari X-Forwarded-For jika ada (melalui proxy)
            $forwardedFor = $request->header('X-Forwarded-For');
            if ($forwardedFor) {
                $realIp = trim(explode(',', $forwardedFor)[0]);
                $request->server->set('REMOTE_ADDR', $realIp);
                $request->server->set('HTTP_CLIENT_IP', $realIp);
            }

            // Ambil token dari storage proxy
            $token = ProxyTokenService::get($identifier);
            if ($token) {
                // Selalu set header Authorization
                $request->headers->set('Authorization', 'Bearer ' . $token);

                // Auto-login ke guard 'web' jika belum terautentikasi
                if (!auth('web')->check()) {
                    try {
                        // Gunakan guard 'api' (Passport) untuk memvalidasi token
                        $user = auth('api')->user();
                        if ($user) {
                            auth('web')->login($user);
                            \Illuminate\Support\Facades\Log::info('InjectBearerFromCookie: Auto-login web guard success', ['user_id' => $user->id]);
                        } else {
                            \Illuminate\Support\Facades\Log::warning('InjectBearerFromCookie: Token valid but user not found in API guard');
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('InjectBearerFromCookie: Auto-login failed', ['error' => $e->getMessage()]);
                    }
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('InjectBearerFromCookie: Token not found for identifier', ['identifier' => $identifier]);
            }
        }

        return $next($request);
    }
}
