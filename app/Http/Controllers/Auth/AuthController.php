<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\NusaworkCallbackRequest;
use App\Http\Requests\Auth\ValidateInviteRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Tenant\RecruiterInvitation;
use App\Services\GoogleLoginService;
use App\Services\NusaworkLoginService;
use App\Services\ProxyTokenService;
use App\Traits\Loggable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * @group Autentikasi
 *
 * API untuk autentikasi pengguna
 */
class AuthController extends Controller
{
    use Loggable;
    
    // Sonar (php:S1192): hindari duplikasi literal 'required|string'
    private const RULE_REQUIRED_STRING = 'required|string';
    private const RULE_NULLABLE_STRING = 'nullable|string';
    private const RULE_NULLABLE_BOOL = 'nullable|bool';

    protected GoogleLoginService $googleLoginService;
    protected NusaworkLoginService $nusaworkLoginService;

    /**
     * Constructor dengan dependency injection
     * 
     * @param GoogleLoginService $googleLoginService
     * @param NusaworkLoginService $nusaworkLoginService
     */
    public function __construct(
        GoogleLoginService $googleLoginService,
        NusaworkLoginService $nusaworkLoginService
    ) {
        $this->googleLoginService = $googleLoginService;
        $this->nusaworkLoginService = $nusaworkLoginService;
    }

    /**
     * User Login with Email and Password
     *
     * This endpoint is used to login a user using email and password.
     *
     * @deprecated This method is deprecated. Use Google or Nusawork login instead.
     * @codeCoverageIgnore
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        $input = $request->validated();

        // Jika login gagal, cek apakah user ada
        $user = User::where('email', $input['email'])->first();

        // Generate Proxy identifier random (UUID)
        $proxyDuration = 60 * 24 * 7;
        $proxyCookieName = config('custom.proxy_key');
        $identifier = Str::uuid()->toString();

        // Coba login dengan kredensial yang diberikan
        if ($user && Hash::check($input['password'], $user->password)) {
            $token = $user->createToken('auth_token')->accessToken;

            // Simpan mapping identifier → token di storage (file)
            ProxyTokenService::put($identifier, $token, $proxyDuration);

            // Set cookie dengan identifier saja (httpOnly)
            $cookie = cookie($proxyCookieName, $identifier, $proxyDuration, null, null, false, true);

            // Update last login
            $user->last_login_ip = $request->ip();
            $user->last_login_at = now();
            $user->last_login_user_agent = $request->userAgent();
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => __('Login Successful'),
                'user' => UserResource::make($user),
                'token_type' => 'Bearer',
                'access_token' => $token,
            ])->cookie($cookie);
        }

        if (! $user) {
            // Jika user tidak ditemukan, buat user baru
            $name = explode('@', $input['email'])[0]; // Ambil bagian sebelum @
            $user = User::create([
                'name' => $name,
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'last_login_ip' => $request->ip(),
                'last_login_at' => now(),
                'last_login_user_agent' => $request->userAgent(),
            ]);

            $token = $user->createToken('auth_token')->accessToken;

            // Simpan mapping identifier → token di storage (file)
            ProxyTokenService::put($identifier, $token, $proxyDuration);

            // Set cookie token dengan nama khusus
            $cookie = cookie($proxyCookieName, $identifier, $proxyDuration, null, null, false, true);

            return response()->json([
                'status' => 'success',
                'message' => __('User created and logged in successfully'),
                'user' => UserResource::make($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ])->cookie($cookie);
        }

        return response()->json([
            'status' => 'error',
            'message' => __('Email or password is incorrect'),
        ], 401);
    }

    /**
     * Redirect to Google login
     *
     * This endpoint is used to redirect a user to the Google login page.
     *
     * @codeCoverageIgnore
     * @return \Illuminate\Http\JsonResponse
     */
    public function redirectToGoogle(Request $request)
    {
        try {
            $customData = [];
            if ($request->has('join_code')) {
                $customData['join_code'] = $request->join_code;
            }

            // Gunakan driver Google tanpa stateless
            $redirectUrl = Socialite::driver('google')
                ->with([
                    'state' => base64_encode(json_encode($customData))
                ])
                ->stateless()->redirect()->getTargetUrl();

            return response()->json([
                'url' => $redirectUrl,
            ]);
        } catch (\Exception $e) {
            $this->logError('Google redirect error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => __('Failed to start Google authentication: :error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * Handle Google callback
     *
     * This endpoint is used to handle the callback from Google after a successful login.
     * It will create or update the user based on the information from Google and return an access token.
     *
     * @codeCoverageIgnore
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $result = $this->googleLoginService->handleCallback($request);

            $data = $result['data'];
            $cookie = $result['cookie'];

            // Return response berdasarkan environment
            if ($result['is_local']) {
                return response()->json($data)->cookie($cookie);
            }

            // Production environment - return HTML response
            $htmlContent = $this->googleLoginService->generateHtmlResponse($data);
            return response()->make($htmlContent, 200, ['Content-Type' => 'text/html'])->cookie($cookie);
        } catch (\Exception $e) {
            // Handle invitation errors dengan view
            if ($e->getCode() === GoogleLoginService::ERROR_USER_FRIENDLY) {
                return view('auth.invitation-error', [
                    'message' => $e->getMessage(),
                    'details' => str_contains($e->getMessage(), 'Portal with invitation code not found')
                        ? __('The invitation code you used is invalid or the portal is no longer active.')
                        : __('Make sure you use the same email as invited and the invitation is still valid (7 days).')
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        } catch (\Throwable $th) {
            $this->logError('Google login error: ' . $th->getMessage(), [
                'request' => $request->all(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => __('Failed to login with Google: :error', ['error' => $th->getMessage()]),
            ], 500);
        }
    }

    /**
     * Handle Nusawork callback
     *
     * This endpoint is used to handle the callback from Nusawork after a successful login.
     * It will create or update the user based on the information from Nusawork and return an access token.
     *
     * @codeCoverageIgnore
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function nusaworkCallback(NusaworkCallbackRequest $request)
    {
        $input = $request->validated();

        try {
            $result = $this->nusaworkLoginService->handleCallback($input, $request);

            // Jika menggunakan session flow
            if (isset($result['session_id'])) {
                return response()->json([
                    'status' => $result['status'],
                    'session_id' => $result['session_id'],
                    'redirect_url' => $result['redirect_url'],
                    'message' => $result['message'],
                ]);
            }

            // Flow normal
            return response()->json([
                'status' => $result['status'],
                'token' => $result['token'],
                'select_tenant' => $result['select_tenant'],
                'user' => $result['user'],
            ])->cookie($result['cookie']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'show_modal' => true,
            ], 400);
        } catch (\Throwable $th) {
            $this->logError('Nusawork callback error: ', [
                'request' => $input,
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => __('Something went wrong. Please try again later.'),
                'show_modal' => true,
            ], 501);
        }
    }


    /**
     * Validate invite recruiter
     *
     * This endpoint validates invite code and returns tenant info
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateInvite(ValidateInviteRequest $request)
    {
        $input = $request->validated();

        // Cari tenant berdasarkan slug
        $tenant = Tenant::where('slug', $input['tenant_slug'])->first();

        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => __('Tenant tidak ditemukan'),
            ], 404);
        }

        // Cari invitation berdasarkan kode di database tenant
        $invitationData = null;
        $tenant->run(function () use ($input, &$invitationData) {
            $invitation = RecruiterInvitation::where('code', $input['code'])->first();

            if ($invitation) {
                $invitationData = [
                    'code' => $invitation->code,
                    'email' => $invitation->email,
                    'status' => $invitation->status,
                    'expires_at' => $invitation->expires_at,
                    'is_valid' => $invitation->isValid(),
                ];
            }
        });

        if (!$invitationData) {
            return response()->json([
                'status' => 'error',
                'message' => __('Link undangan tidak valid atau sudah kadaluarsa'),
            ], 404);
        }

        // Validasi apakah invitation masih valid
        if (!$invitationData['is_valid']) {
            return response()->json([
                'status' => 'error',
                'message' => __('Link undangan sudah kadaluarsa'),
            ], 410);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'tenant' => [
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'code' => $invitationData['code'],
                ],
                'invite_code' => $input['code'],
                'invited_email' => $invitationData['email'],
            ],
        ]);
    }

    /**
     * Logout user
     *
     * This endpoint is used to logout a user who is currently logged in.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // @codeCoverageIgnoreStart
        // Revoke token - requires Passport OAuth infrastructure
        if ($request->user() && method_exists($request->user(), 'token') && $request->user()->token()) {
            $request->user()->token()->revoke();
        }
        // @codeCoverageIgnoreEnd

        // Testable part - cleanup proxy token
        $identifier = $request->cookie(config('custom.proxy_key'));
        if ($identifier) {
            ProxyTokenService::delete($identifier);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Logout successful'),
        ]);
    }
}
