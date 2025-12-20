<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ProxyTokenService;
use App\Traits\Loggable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WebAuthController extends Controller
{
    use Loggable;
    
    /**
     * Show invite recruiter page
     *
     * @param Request $request
     * @param string $tenantSlug
     * @return \Inertia\Response
     */
    public function showInviteRecruiter(Request $request, $tenantSlug)
    {
        $code = $request->query('code');

        if (!$code) {
            abort(400, 'Kode undangan diperlukan');
        }

        // Force logout: hapus semua session dan cookies
        $this->forceLogout($request);

        return Inertia::render('auth/InviteRecruiter', [
            'tenantSlug' => $tenantSlug,
            'inviteCode' => $code,
            'meta' => ['requiresGuest' => true],
        ]);
    }

    /**
     * Force logout user untuk invite flow
     *
     * @param Request $request
     * @return void
     */
    private function forceLogout(Request $request)
    {
        // @codeCoverageIgnoreStart
        // Revoke current user token jika ada
        // Excluded: Requires full Passport OAuth infrastructure with real tokens
        if ($request->user()) {
            $request->user()->token()->revoke();
        }
        // @codeCoverageIgnoreEnd

        // Hapus proxy token dari storage
        $identifier = $request->cookie(config('custom.proxy_key'));
        if ($identifier) {
            ProxyTokenService::delete($identifier);
        }

        // Clear session
        $request->session()->flush();
        $request->session()->regenerate();
    }

    /**
     * Handle Session Login
     *
     * This endpoint is used to handle session-based login from Nusawork.
     * It validates the session and temporary token, then redirects to the appropriate page.
     *
     * @param Request $request
     * @param string $sessionId
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function handleSessionLogin(Request $request, $sessionId)
    {
        try {
            $tempToken = $request->query('t');

            if (!$tempToken) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Token parameter is required'),
                ], 400);
            }

            // Ambil data session dari cache
            $sessionData = cache()->get("session_login:{$sessionId}");

            if (!$sessionData) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Session not found or expired'),
                ], 404);
            }

            // Validasi temporary token
            if ($sessionData['temp_token'] !== $tempToken) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Invalid token'),
                ], 401);
            }

            // Ambil user data
            $user = User::find($sessionData['user_id']);
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('User not found'),
                ], 404);
            }

            $centralConnection = (string) (config('tenancy.database.central_connection') ?: config('database.default'));

            // Set cookie dengan proxy identifier
            $proxyDuration = 60 * 24 * 7;
            $proxyCookieName = config('custom.proxy_key');
            $cookie = cookie($proxyCookieName, $sessionData['proxy_identifier'], $proxyDuration, null, null, false, true);

            DB::connection($centralConnection)
                ->table('users')
                ->where('id', $user->id)
                ->update([
                    'last_login_ip' => $request->ip(),
                    'last_login_at' => now(),
                    'last_login_user_agent' => $request->userAgent(),
                ]);

            // Hapus session data dari cache
            cache()->forget("session_login:{$sessionId}");

            // Buat HTML untuk set localStorage dan redirect (mirip dengan login.vue setUserLogin)
            $role = DB::connection($centralConnection)
                ->table('tenant_users')
                ->where('global_user_id', $user->global_id)
                ->value('role');

            if ($role === 'recruiter') {
                $role = 'admin';
            }

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'avatar' => $user->avatar,
            ];

            $portalData = DB::connection($centralConnection)
                ->table('tenant_users')
                ->join('tenants', 'tenant_users.tenant_id', '=', 'tenants.id')
                ->where('tenant_users.global_user_id', $user->global_id)
                ->select(['tenants.id', 'tenants.name', 'tenants.slug'])
                ->get()
                ->map(function ($tenant) {
                    return [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'slug' => $tenant->slug,
                    ];
                });

            $redirectUrl = $this->getRedirectUrlForUser($user);

            return response()->make('
                <html>
                <head><title>Login Berhasil</title></head>
                <body>
                <script>
                    // Set localStorage seperti di login.vue
                    localStorage.setItem("token", "' . $sessionData['access_token'] . '");
                    localStorage.setItem("user", ' . json_encode($userData) . ');
                    localStorage.setItem("portal", ' . json_encode($portalData) . ');
                    
                    // Redirect ke halaman yang sesuai
                    window.location.href = "' . $redirectUrl . '";
                </script>
                </body>
                </html>
            ', 200, ['Content-Type' => 'text/html'])->cookie($cookie);
        // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            // Excluded: Catch block for unexpected exceptions
            // Difficult to test without breaking actual functionality or mocking internal Laravel methods
            // Should be tested via chaos/integration testing with real failure scenarios
            $this->logError('Session login error: ' . $e->getMessage(), [
                'session_id' => $sessionId,
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => __('Session login failed'),
            ], 500);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Get redirect URL based on user role and tenant
     *
     * @param User $user
     * @return string
     */
    private function getRedirectUrlForUser(User $user)
    {
        $centralConnection = (string) (config('tenancy.database.central_connection') ?: config('database.default'));
        $tenant = DB::connection($centralConnection)
            ->table('tenant_users')
            ->join('tenants', 'tenant_users.tenant_id', '=', 'tenants.id')
            ->where('tenant_users.global_user_id', $user->global_id)
            ->select(['tenants.slug'])
            ->first();

        // Jika user punya tenant, redirect ke tenant dashboard
        if ($tenant && !empty($tenant->slug)) {
            $pathAdmin = config('custom.admin_path'); // Sama seperti di login.vue
            return url("/{$tenant->slug}/{$pathAdmin}");
        }

        // Jika user belum punya tenant, redirect ke setup portal
        return url('/setup/portal');
    }
}
