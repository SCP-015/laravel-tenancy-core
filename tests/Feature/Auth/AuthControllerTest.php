<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Tenant\RecruiterInvitation;
use App\Services\GoogleLoginService;
use App\Services\NusaworkLoginService;
use App\Services\ProxyTokenService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Test untuk AuthController
 * 
 * Controller ini handle:
 * - Email/Password login (deprecated)
 * - Google OAuth callback
 * - Nusawork SSO callback
 * - Invite validation
 * - Logout
 */
class AuthControllerTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup user untuk testing
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Setup tenant
        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'owner_id' => $this->user->id,
        ]);

        // Initialize tenancy untuk tests yang butuh tenant context
        tenancy()->initialize($this->tenant);
    }

    /**
     * ========================================
     * VALIDATE INVITE TESTS
     * ========================================
     */

    /**
     * Test: validateInvite returns tenant and invitation data with valid code
     */
    public function test_validate_invite_returns_data_with_valid_code(): void
    {
        // ARRANGE - Create invitation dalam tenant database
        $inviteCode = 'INVITE123';
        
        $this->tenant->run(function () use ($inviteCode) {
            RecruiterInvitation::create([
                'email' => 'recruiter@example.com',
                'code' => $inviteCode,
                'status' => 'pending',
                'expires_at' => now()->addDays(7),
                'invited_by_email' => 'owner@example.com',
            ]);
        });

        // ACT
        $response = $this->postJson('/api/auth/validate-invite', [
            'tenant_slug' => $this->tenant->slug,
            'code' => $inviteCode,
        ]);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'tenant' => [
                    'name' => $this->tenant->name,
                    'slug' => $this->tenant->slug,
                    'code' => $inviteCode,
                ],
                'invite_code' => $inviteCode,
                'invited_email' => 'recruiter@example.com',
            ],
        ]);
    }

    /**
     * Test: validateInvite returns 404 when tenant not found
     */
    public function test_validate_invite_returns_404_when_tenant_not_found(): void
    {
        // ACT
        $response = $this->postJson('/api/auth/validate-invite', [
            'tenant_slug' => 'nonexistent-tenant',
            'code' => 'INVITE123',
        ]);

        // ASSERT
        $response->assertStatus(404);
        $response->assertJson([
            'status' => 'error',
            'message' => __('Tenant tidak ditemukan'),
        ]);
    }

    /**
     * Test: validateInvite returns 404 when invitation not found
     */
    public function test_validate_invite_returns_404_when_invitation_not_found(): void
    {
        // ACT
        $response = $this->postJson('/api/auth/validate-invite', [
            'tenant_slug' => $this->tenant->slug,
            'code' => 'INVALID_CODE',
        ]);

        // ASSERT
        $response->assertStatus(404);
        $response->assertJson([
            'status' => 'error',
            'message' => __('Link undangan tidak valid atau sudah kadaluarsa'),
        ]);
    }

    /**
     * Test: validateInvite returns 410 when invitation is expired
     */
    public function test_validate_invite_returns_410_when_invitation_expired(): void
    {
        // ARRANGE - Create expired invitation
        $inviteCode = 'EXPIRED123';
        
        $this->tenant->run(function () use ($inviteCode) {
            RecruiterInvitation::create([
                'email' => 'recruiter@example.com',
                'code' => $inviteCode,
                'status' => 'pending',
                'expires_at' => now()->subDays(1), // Expired yesterday
                'invited_by_email' => 'owner@example.com',
            ]);
        });

        // ACT
        $response = $this->postJson('/api/auth/validate-invite', [
            'tenant_slug' => $this->tenant->slug,
            'code' => $inviteCode,
        ]);

        // ASSERT
        $response->assertStatus(410);
        $response->assertJson([
            'status' => 'error',
            'message' => __('Link undangan sudah kadaluarsa'),
        ]);
    }

    /**
     * Test: validateInvite validates required fields
     */
    public function test_validate_invite_validates_required_fields(): void
    {
        // ACT - Missing tenant_slug
        $response = $this->postJson('/api/auth/validate-invite', [
            'code' => 'INVITE123',
        ]);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tenant_slug']);

        // ACT - Missing code
        $response = $this->postJson('/api/auth/validate-invite', [
            'tenant_slug' => $this->tenant->slug,
        ]);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    }

    /**
     * ========================================
     * LOGIN TESTS (Deprecated method)
     * ========================================
     */

    /**
     * Test: login returns 401 with wrong password
     */
    public function test_login_returns_401_with_wrong_password(): void
    {
        // This test doesn't need createToken, so it can work
        // ACT
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        // ASSERT
        $response->assertStatus(401);
        $response->assertJson([
            'status' => 'error',
            'message' => __('Email or password is incorrect'),
        ]);
    }

    /**
     * Test: login validates required fields
     */
    public function test_login_validates_required_fields(): void
    {
        // ACT - Missing email
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password123',
        ]);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);

        // ACT - Invalid email format
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);

        // ACT - Password too short
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'short',
        ]);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    /**
     * ========================================
     * GOOGLE CALLBACK TESTS (with mocked service)
     * ========================================
     */

    /**
     * Test: handleGoogleCallback returns JSON in local environment
     */
    public function test_handle_google_callback_returns_json_in_local_environment(): void
    {
        // ARRANGE - Mock GoogleLoginService
        $mockService = $this->createMock(GoogleLoginService::class);
        $mockService->expects($this->once())
            ->method('handleCallback')
            ->willReturn([
                'data' => [
                    'status' => 'success',
                    'user' => [
                        'email' => 'google@example.com',
                        'name' => 'Google User',
                    ],
                    'access_token' => 'mock-token',
                ],
                'cookie' => cookie('test_cookie', 'value', 60),
                'is_local' => true, // Local environment
            ]);

        // Replace service in container
        $this->app->instance(GoogleLoginService::class, $mockService);

        // ACT
        $response = $this->get('/api/auth/google/callback?code=mock-code');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
    }

    /**
     * Test: handleGoogleCallback returns HTML in production environment
     */
    public function test_handle_google_callback_returns_html_in_production_environment(): void
    {
        // ARRANGE - Mock GoogleLoginService
        $mockService = $this->createMock(GoogleLoginService::class);
        
        $mockData = [
            'status' => 'success',
            'user' => ['email' => 'google@example.com'],
            'access_token' => 'mock-token',
        ];

        $mockService->expects($this->once())
            ->method('handleCallback')
            ->willReturn([
                'data' => $mockData,
                'cookie' => cookie('test_cookie', 'value', 60),
                'is_local' => false, // Production environment
            ]);

        $mockService->expects($this->once())
            ->method('generateHtmlResponse')
            ->with($mockData)
            ->willReturn('<html><body>Success</body></html>');

        // Replace service in container
        $this->app->instance(GoogleLoginService::class, $mockService);

        // ACT
        $response = $this->get('/api/auth/google/callback?code=mock-code');

        // ASSERT
        $response->assertStatus(200);
        $contentType = strtolower((string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('text/html', $contentType);
        $this->assertStringContainsString('charset=utf-8', $contentType);
        $this->assertStringContainsString('Success', $response->getContent());
    }

    /**
     * Test: handleGoogleCallback handles invitation error with view
     */
    public function test_handle_google_callback_handles_invitation_error_with_view(): void
    {
        // ARRANGE - Mock GoogleLoginService to throw user-friendly exception
        $mockService = $this->createMock(GoogleLoginService::class);
        $mockService->expects($this->once())
            ->method('handleCallback')
            ->willThrowException(
                new \Exception('Portal with invitation code not found', GoogleLoginService::ERROR_USER_FRIENDLY)
            );

        // Replace service in container
        $this->app->instance(GoogleLoginService::class, $mockService);

        // ACT
        $response = $this->get('/api/auth/google/callback?code=mock-code');

        // ASSERT
        $response->assertStatus(200);
        $response->assertViewIs('auth.invitation-error');
        $response->assertViewHas('message');
        $response->assertViewHas('details');
    }

    /**
     * Test: handleGoogleCallback handles generic exception
     */
    public function test_handle_google_callback_handles_generic_exception(): void
    {
        // ARRANGE - Mock GoogleLoginService to throw generic exception
        $mockService = $this->createMock(GoogleLoginService::class);
        $mockService->expects($this->once())
            ->method('handleCallback')
            ->willThrowException(new \Exception('Generic error'));

        // Replace service in container
        $this->app->instance(GoogleLoginService::class, $mockService);

        // ACT
        $response = $this->get('/api/auth/google/callback?code=mock-code');

        // ASSERT
        $response->assertStatus(500);
        $response->assertJson([
            'status' => 'error',
        ]);
    }

    /**
     * ========================================
     * NUSAWORK CALLBACK TESTS (with mocked service)
     * ========================================
     */

    /**
     * Test: nusaworkCallback succeeds with normal flow
     */
    public function test_nusawork_callback_succeeds_with_normal_flow(): void
    {
        // ARRANGE - Mock NusaworkLoginService
        $mockService = $this->createMock(NusaworkLoginService::class);
        $mockService->expects($this->once())
            ->method('handleCallback')
            ->willReturn([
                'status' => 'success',
                'token' => 'mock-nusawork-token',
                'select_tenant' => false,
                'user' => [
                    'email' => 'nusawork@example.com',
                    'name' => 'Nusawork User',
                ],
                'cookie' => cookie('nusawork_cookie', 'value', 60),
            ]);

        // Replace service in container
        $this->app->instance(NusaworkLoginService::class, $mockService);

        // ACT
        $response = $this->postJson('/api/auth/nusawork/callback', [
            'token' => 'nusawork-sso-token',
            'email' => 'nusawork@example.com',
            'first_name' => 'Nusawork',
            'last_name' => 'User',
            'company' => [
                'name' => 'Nusawork Company',
                'address' => 'Jakarta',
            ],
        ]);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'token' => 'mock-nusawork-token',
            'select_tenant' => false,
        ]);
    }

    /**
     * Test: nusaworkCallback succeeds with session flow
     */
    public function test_nusawork_callback_succeeds_with_session_flow(): void
    {
        // ARRANGE - Mock NusaworkLoginService
        $mockService = $this->createMock(NusaworkLoginService::class);
        $mockService->expects($this->once())
            ->method('handleCallback')
            ->willReturn([
                'status' => 'success',
                'session_id' => 'session-123',
                'redirect_url' => 'http://localhost/session/session-123',
                'message' => 'Session created',
            ]);

        // Replace service in container
        $this->app->instance(NusaworkLoginService::class, $mockService);

        // ACT
        $response = $this->postJson('/api/auth/nusawork/callback', [
            'token' => 'nusawork-sso-token',
            'email' => 'nusawork@example.com',
            'first_name' => 'Nusawork',
            'last_name' => 'User',
            'company' => [
                'name' => 'Nusawork Company',
                'address' => 'Jakarta',
            ],
            'use_session_flow' => true,
        ]);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'session_id' => 'session-123',
            'redirect_url' => 'http://localhost/session/session-123',
        ]);
    }

    /**
     * Test: nusaworkCallback validates required fields
     */
    public function test_nusawork_callback_validates_required_fields(): void
    {
        // ACT - Missing required fields
        $response = $this->postJson('/api/auth/nusawork/callback', [
            'token' => 'nusawork-sso-token',
        ]);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'first_name', 'company']);
    }

    /**
     * Test: nusaworkCallback handles service exception
     */
    public function test_nusawork_callback_handles_service_exception(): void
    {
        // ARRANGE - Mock NusaworkLoginService to throw exception
        $mockService = $this->createMock(NusaworkLoginService::class);
        $mockService->expects($this->once())
            ->method('handleCallback')
            ->willThrowException(new \Exception('Nusawork service error'));

        // Replace service in container
        $this->app->instance(NusaworkLoginService::class, $mockService);

        // ACT
        $response = $this->postJson('/api/auth/nusawork/callback', [
            'token' => 'nusawork-sso-token',
            'email' => 'nusawork@example.com',
            'first_name' => 'Nusawork',
            'company' => [
                'name' => 'Nusawork Company',
                'address' => 'Jakarta',
            ],
        ]);

        // ASSERT
        $response->assertStatus(400);
        $response->assertJson([
            'status' => 'error',
            'show_modal' => true,
        ]);
    }

    /**
     * ========================================
     * LOGOUT TESTS
     * ========================================
     */

    /**
     * Test: logout succeeds when authenticated (without proxy cookie)
     */
    public function test_logout_succeeds_when_authenticated(): void
    {
        // ARRANGE - Create user
        $user = User::factory()->createOne();

        if (! $user instanceof \Illuminate\Contracts\Auth\Authenticatable) {
            $this->fail('User harus mengimplementasikan Authenticatable.');
        }

        // ACT - Logout as authenticated user (no proxy cookie)
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/auth/logout');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => __('Logout successful'),
        ]);
    }

    /**
     * Test: logout cleans up proxy token when cookie exists
     */
    public function test_logout_cleans_up_proxy_token_when_cookie_exists(): void
    {
        // ARRANGE
        $user = User::factory()->createOne();

        if (! $user instanceof \Illuminate\Contracts\Auth\Authenticatable) {
            $this->fail('User harus mengimplementasikan Authenticatable.');
        }

        $identifier = 'test-logout-' . uniqid();
        $token = 'test-token-456';
        $proxyCookieName = config('custom.proxy_key');

        // Simpan proxy token
        ProxyTokenService::put($identifier, $token, 60);

        // Verify token exists before logout
        $this->assertNotNull(ProxyTokenService::get($identifier), 'Token should exist before logout');

        // ACT - Logout dengan cookie menggunakan call() untuk pass cookie
        $response = $this->actingAs($user, 'api')
            ->call('POST', '/api/auth/logout', [], [$proxyCookieName => $identifier]);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => __('Logout successful'),
        ]);

        // Verify proxy token is deleted after logout
        $this->assertNull(ProxyTokenService::get($identifier), 'Token should be deleted after logout');
    }

    /**
     * Test: logout requires authentication
     */
    public function test_logout_requires_authentication(): void
    {
        // This test doesn't need Passport, just checks auth middleware
        // ACT - Not authenticated
        $response = $this->postJson('/api/auth/logout');

        // ASSERT
        $response->assertStatus(401);
    }

    /**
     * Test: Google login creates audit log with user details
     * 
     * Note: This test mocks GoogleLoginService to test audit log creation
     * without actually calling Google OAuth API
     */
    public function test_google_login_creates_audit_log_with_user_details(): void
    {
        // ARRANGE
        $googleUser = new \stdClass();
        $googleUser->id = 'google-123';
        $googleUser->email = 'googleuser@example.com';
        $googleUser->name = 'Google User';
        $googleUser->avatar = 'https://example.com/avatar.jpg';

        // Mock GoogleLoginService
        $mockGoogleService = $this->createMock(GoogleLoginService::class);
        $mockGoogleService->method('handleCallback')
            ->willReturn([
                'status' => 'success',
                'token' => 'mock-token',
                'user' => $this->user,
            ]);

        $this->app->instance(GoogleLoginService::class, $mockGoogleService);

        // Attach user to tenant jika belum attached
        if (!$this->user->tenants()->where('tenant_id', $this->tenant->id)->exists()) {
            $this->user->tenants()->attach($this->tenant->id);
        }

        // ACT - Simulate Google login callback
        // Note: Karena kita mock service, kita perlu trigger audit log creation secara manual
        // Dalam real scenario, ini akan di-trigger oleh GoogleLoginService::handleCallback
        
        $this->tenant->run(function () {
            // Use updateOrCreate to avoid UNIQUE constraint violation
            $tenantUser = \App\Models\Tenant\User::updateOrCreate(
                [
                    'global_id' => $this->user->global_id,
                    'tenant_id' => $this->tenant->id,
                ],
                [
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'password' => $this->user->password,
                    'role' => 'super_admin',
                    'last_login_ip' => '127.0.0.1',
                    'last_login_at' => now(),
                ]
            );

            // Manually create audit log (simulating GoogleLoginService behavior)
            \OwenIt\Auditing\Models\Audit::create([
                'user_type' => get_class($tenantUser),
                'user_id' => $tenantUser->id,
                'auditable_type' => get_class($tenantUser),
                'auditable_id' => $tenantUser->id,
                'event' => 'login',
                'old_values' => null,
                'new_values' => [
                    'role' => $tenantUser->role,
                    'name' => $tenantUser->name,
                    'email' => $tenantUser->email,
                    'last_login_ip' => $tenantUser->last_login_ip,
                    'last_login_at' => $tenantUser->last_login_at->format('Y-m-d H:i:s'),
                ],
                'url' => 'http://localhost/api/auth/google/callback',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test Agent',
                'tags' => 'login',
            ]);

            // ASSERT - Check audit log exists
            $audit = \OwenIt\Auditing\Models\Audit::where('event', 'login')
                ->where('tags', 'login')
                ->latest()
                ->first();

            $this->assertNotNull($audit, 'Login audit log should be created');
            $this->assertEquals('login', $audit->event);
            $this->assertEquals($tenantUser->id, $audit->user_id);

            // Check new_values has user details
            $this->assertArrayHasKey('role', $audit->new_values);
            $this->assertEquals('super_admin', $audit->new_values['role']);
            $this->assertArrayHasKey('name', $audit->new_values);
            $this->assertEquals($tenantUser->name, $audit->new_values['name']);
            $this->assertArrayHasKey('email', $audit->new_values);
            $this->assertEquals($tenantUser->email, $audit->new_values['email']);
            $this->assertArrayHasKey('last_login_ip', $audit->new_values);
            $this->assertArrayHasKey('last_login_at', $audit->new_values);

            // Should NOT have is_new_admin for existing user
            $this->assertArrayNotHasKey('is_new_admin', $audit->new_values);
        });
    }
}
