<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant;
use Spatie\Permission\Models\Permission;
use Tests\Feature\TenantTestCase;

/**
 * Comprehensive test suite untuk SettingController
 * Target: 100% coverage
 */
class SettingControllerTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set locale to English untuk testing
        app()->setLocale('en');

        // Create permission yang dibutuhkan
        Permission::firstOrCreate(['name' => 'settings.generate_code', 'guard_name' => 'api']);
    }

    /**
     * Test: GenerateCode generates new code successfully
     */
    public function test_generate_code_generates_new_code_successfully(): void
    {
        // ARRANGE
        $user = $this->actingAsTenantOwner();
        
        // Give permission
        $this->tenant->run(function () use ($user) {
            $tenantUser = \App\Models\Tenant\User::find($user->id);
            $tenantUser->givePermissionTo('settings.generate_code');
        });

        $oldCode = $this->tenant->code;

        // ACT
        $response = $this->postJson("/{$this->tenant->slug}/api/settings/refresh-code");

        // ASSERT
        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'message',
            'code',
        ]);
        $response->assertJsonPath('status', 'success');

        // Verify code changed in database
        $this->tenant->refresh();
        $this->assertNotEquals($oldCode, $this->tenant->code);
        $this->assertEquals($response->json('code'), $this->tenant->code);
    }

    /**
     * Test: GenerateCode returns new code in response
     */
    public function test_generate_code_returns_new_code_in_response(): void
    {
        // ARRANGE
        $user = $this->actingAsTenantOwner();
        
        $this->tenant->run(function () use ($user) {
            $tenantUser = \App\Models\Tenant\User::find($user->id);
            $tenantUser->givePermissionTo('settings.generate_code');
        });

        // ACT
        $response = $this->postJson("/{$this->tenant->slug}/api/settings/refresh-code");

        // ASSERT
        $response->assertOk();
        $newCode = $response->json('code');
        $this->assertNotNull($newCode);
        $this->assertIsString($newCode);
        $this->assertNotEmpty($newCode);
    }

    /**
     * Test: GenerateCode creates unique codes
     */
    public function test_generate_code_creates_unique_codes(): void
    {
        // ARRANGE
        $user = $this->actingAsTenantOwner();
        
        $this->tenant->run(function () use ($user) {
            $tenantUser = \App\Models\Tenant\User::find($user->id);
            $tenantUser->givePermissionTo('settings.generate_code');
        });

        // ACT - Generate multiple codes
        $response1 = $this->postJson("/{$this->tenant->slug}/api/settings/refresh-code");
        $code1 = $response1->json('code');

        $response2 = $this->postJson("/{$this->tenant->slug}/api/settings/refresh-code");
        $code2 = $response2->json('code');

        $response3 = $this->postJson("/{$this->tenant->slug}/api/settings/refresh-code");
        $code3 = $response3->json('code');

        // ASSERT - All codes should be different
        $this->assertNotEquals($code1, $code2);
        $this->assertNotEquals($code2, $code3);
        $this->assertNotEquals($code1, $code3);
    }

    /**
     * Test: GenerateCode fails without permission
     */
    public function test_index_settings_fails_without_permission(): void
    {
        // ARRANGE - Use regular recruiter without permission
        $users = $this->createTenantUser([
            'role' => 'admin',
        ]);

        // Ensure no permissions
        $this->tenant->run(function () use ($users) {
            $users->tenantUser->syncPermissions([]);
        });

        // ACT
        $response = $this->actingAs($users->centralUser, 'api')
            ->postJson("/{$this->tenant->slug}/api/settings/refresh-code");

        // ASSERT
        $response->assertStatus(403);
    }

    /**
     * Test: GenerateCode fails when not authenticated
     */
    public function test_generate_code_fails_when_not_authenticated(): void
    {
        // ACT - No authentication
        $response = $this->postJson("/{$this->tenant->slug}/api/settings/refresh-code");

        // ASSERT
        $response->assertUnauthorized();
    }

    /**
     * Test: GenerateCode updates correct tenant
     */
    public function test_generate_code_updates_correct_tenant(): void
    {
        // ARRANGE
        $user = $this->actingAsTenantOwner();
        
        $this->tenant->run(function () use ($user) {
            $tenantUser = \App\Models\Tenant\User::find($user->id);
            $tenantUser->givePermissionTo('settings.generate_code');
        });

        $oldCode = $this->tenant->code;

        // ACT
        $response = $this->postJson("/{$this->tenant->slug}/api/settings/refresh-code");

        // ASSERT
        $response->assertOk();
        
        // Verify only this tenant's code changed
        $this->tenant->refresh();
        $this->assertNotEquals($oldCode, $this->tenant->code);
        
        // Verify tenant is correct
        $tenantFromDb = Tenant::find($this->tenant->id);
        $this->assertEquals($response->json('code'), $tenantFromDb->code);
    }

    /**
     * Test: GenerateCode returns localized success message
     */
    public function test_generate_code_returns_localized_success_message(): void
    {
        // ARRANGE
        $user = $this->actingAsTenantOwner();
        
        $this->tenant->run(function () use ($user) {
            $tenantUser = \App\Models\Tenant\User::find($user->id);
            $tenantUser->givePermissionTo('settings.generate_code');
        });

        // ACT
        $response = $this->postJson("/{$this->tenant->slug}/api/settings/refresh-code");

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('message', __('Company code refreshed successfully'));
    }

    /**
     * Test: GenerateCode works with recruiter role if has permission
     */
    public function test_index_settings_success_with_permission(): void
    {
        // ARRANGE
        $users = $this->createTenantUser([
            'role' => 'admin',
        ]);

        // Give permission to recruiter
        $this->tenant->run(function () use ($users) {
            $users->tenantUser->givePermissionTo('settings.generate_code');
        });

        $oldCode = $this->tenant->code;

        // ACT
        $response = $this->actingAs($users->centralUser, 'api')
            ->postJson("/{$this->tenant->slug}/api/settings/refresh-code");

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('status', 'success');

        // Verify code changed
        $this->tenant->refresh();
        $this->assertNotEquals($oldCode, $this->tenant->code);
    }

    /**
     * Test: GenerateCode validates code format
     */
    public function test_generate_code_validates_code_format(): void
    {
        // ARRANGE
        $user = $this->actingAsTenantOwner();
        
        $this->tenant->run(function () use ($user) {
            $tenantUser = \App\Models\Tenant\User::find($user->id);
            $tenantUser->givePermissionTo('settings.generate_code');
        });

        // ACT
        $response = $this->postJson("/{$this->tenant->slug}/api/settings/refresh-code");

        // ASSERT
        $response->assertOk();
        $code = $response->json('code');
        
        // Verify code format (assuming it follows certain pattern)
        $this->assertIsString($code);
        $this->assertGreaterThan(0, strlen($code));
    }
}
