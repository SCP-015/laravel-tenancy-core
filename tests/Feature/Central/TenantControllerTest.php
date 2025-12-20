<?php

namespace Tests\Feature\Central;

use App\Models\Tenant;
use App\Models\TenantSlugHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test untuk TenantController
 * 
 * Note: Menggunakan TestCase biasa (bukan TenantTestCase) karena
 * TenantController adalah CENTRAL controller yang manage tenants,
 * bukan tenant-specific controller yang butuh tenant context.
 */
class TenantControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $centralUser;
    protected User $centralUserRecruiter;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat user dan tenant untuk testing
        $this->centralUser = User::factory()->create();
        $this->centralUserRecruiter = User::factory()->create();
        $this->tenant = Tenant::factory()->create(['owner_id' => $this->centralUser->id]);
        
        // Attach recruiter ke tenant (owner sudah otomatis di-attach oleh factory)
        $this->centralUserRecruiter->tenants()->attach($this->tenant->id);
    }
    /**
     * Test: Index returns list of user's tenants
     */
    public function test_index_returns_list_of_users_tenants(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');

        // ACT
        $response = $this->getJson('/api/portal');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'code',
                    'slug',
                ],
            ],
        ]);
        $response->assertJsonPath('data.0.id', $this->tenant->id);
    }

    /**
     * Test: Index requires authentication
     */
    public function test_index_requires_authentication(): void
    {
        // ACT
        $response = $this->getJson('/api/portal');

        // ASSERT
        $response->assertStatus(401);
    }

    /**
     * Test: Guest portal returns tenant info
     * 
     * Flow (sesuai request):
     * 1. Buat tenant terlebih dahulu (seperti TenantTestCase)
     * 2. Ambil slug tenant
     * 3. Initialize tenant context
     * 4. Hit API public portal
     * 
     * Note: Test ini mungkin gagal karena route /api/public/portal
     * perlu tenant routes registration via middleware.
     * Silakan cek manual untuk debugging.
     */
    public function test_guest_portal_returns_tenant_info(): void
    {
        // STEP 1: Buat tenant terlebih dahulu dengan slug yang jelas
        $guestTenant = Tenant::factory()->create([
            'name' => 'Guest Portal Test Company',
            'slug' => 'guest-test-portal',
            'code' => 'GUESTTEST',
        ]);

        // STEP 2: Ambil slug tenant
        $tenantSlug = $guestTenant->slug;
        $this->assertNotNull($tenantSlug, 'Tenant slug harus ada');

        // STEP 3: Initialize tenant context (simulasi request ke tenant domain)
        tenancy()->initialize($guestTenant);

        try {
            // STEP 4: Hit API public portal
            // URL: /api/public/portal (dari routes/api-tenant.php)
            // Note: Perlu tambahkan slug di URL path untuk routing ke tenant
            $response = $this->getJson($tenantSlug.'/api/public/portal');

            // ASSERT - Cek response structure
            $response->assertStatus(200);
            $response->assertJsonStructure([
                'portal' => [
                    'id',
                    'name',
                    'code',
                    'slug',
                ],
            ]);

            // Verify tenant data
            $response->assertJsonPath('portal.id', $guestTenant->id);
            $response->assertJsonPath('portal.slug', $tenantSlug);
            $response->assertJsonPath('portal.code', 'GUESTTEST');
            $response->assertJsonPath('portal.name', 'Guest Portal Test Company');
        } finally {
            // CLEANUP - End tenancy context
            tenancy()->end();
        }
    }

    /**
     * Test: Store creates new tenant
     */
    public function test_store_creates_new_tenant(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');
        $data = [
            'name' => 'New Test Portal',
            'slug' => 'new-test-portal',
        ];

        // ACT
        $response = $this->postJson('/api/portal', $data);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'portal' => [
                'id',
                'name',
                'code',
                'slug',
            ],
        ]);
        $response->assertJsonFragment(['status' => 'success']);
        
        $this->assertDatabaseHas('tenants', [
            'name' => 'New Test Portal',
            'slug' => 'new-test-portal',
        ]);
    }

    /**
     * Test: Store validates required fields
     */
    public function test_store_validates_required_fields(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');

        // ACT
        $response = $this->postJson('/api/portal', []);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Test: Store validates name minimum length
     */
    public function test_store_validates_name_minimum_length(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');

        // ACT
        $response = $this->postJson('/api/portal', [
            'name' => 'AB',
        ]);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Test: Store fails when slug already exists (database constraint)
     * 
     * Test ini akan hit line 73-80 di TenantController (error path)
     * ketika TenantService::store() return error karena duplicate slug.
     */
    public function test_store_fails_when_slug_already_exists(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');
        
        // Buat tenant dengan slug tertentu
        Tenant::factory()->create([
            'name' => 'Existing Company',
            'slug' => 'existing-company-slug',
        ]);

        // ACT - Coba buat tenant baru dengan slug yang sama
        $response = $this->postJson('/api/portal', [
            'name' => 'New Company Name',
            'slug' => 'existing-company-slug', // ← Duplicate slug
        ]);

        // ASSERT - Should return warning dengan status 422 dari service
        $response->assertStatus(422); // Line 80: 'warning' == $result['status'] ? 422 : 500
        $response->assertJsonFragment(['status' => 'warning']); // Line 75
        $response->assertJsonStructure([
            'status',   // Line 75
            'message',  // Line 76
            'error',    // Line 77
            'trace',    // Line 78
        ]);
        
        // Verify tenant baru TIDAK dibuat
        $this->assertDatabaseMissing('tenants', [
            'name' => 'New Company Name',
        ]);
    }

    /**
     * Test: Store fails when name already exists
     * 
     * Test kedua untuk error path - duplicate name (tanpa slug).
     * Ini memastikan semua branch di line 73-80 ter-cover.
     */
    public function test_store_fails_when_name_already_exists(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');
        
        // Buat tenant dengan nama tertentu
        Tenant::factory()->create([
            'name' => 'Duplicate Name Company',
        ]);

        // ACT - Coba buat tenant baru dengan nama yang sama (tanpa slug)
        $response = $this->postJson('/api/portal', [
            'name' => 'Duplicate Name Company', // ← Duplicate name
            // Tidak ada slug, jadi akan di-generate otomatis
        ]);

        // ASSERT - Should return warning dengan status 422
        $response->assertStatus(422);
        $response->assertJsonFragment(['status' => 'warning']);
        $response->assertJsonPath('message', __('Portal already exists. Use a different name'));
        
        // Verify hanya ada 1 tenant dengan nama ini (yang pertama)
        $this->assertEquals(1, Tenant::where('name', 'Duplicate Name Company')->count());
    }

    /**
     * Test: Store requires authentication
     */
    public function test_store_requires_authentication(): void
    {
        // ACT
        $response = $this->postJson('/api/portal', [
            'name' => 'Test Portal',
        ]);

        // ASSERT
        $response->assertStatus(401);
    }

    /**
     * Test: Join allows user to join tenant with valid code
     */
    public function test_join_allows_user_to_join_tenant_with_valid_code(): void
    {
        // ARRANGE
        $newUser = User::factory()->create();
        $this->actingAs($newUser, 'api');

        // ACT
        $response = $this->postJson('/api/portal/join', [
            'code' => $this->tenant->code,
        ]);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'success']);
        $response->assertJsonPath('portal.id', $this->tenant->id);
        
        // Verify user is attached to tenant
        $this->assertTrue($newUser->tenants()->where('tenant_id', $this->tenant->id)->exists());
    }

    /**
     * Test: Join returns error when code not found
     */
    public function test_join_returns_error_when_code_not_found(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');

        // ACT
        $response = $this->postJson('/api/portal/join', [
            'code' => 'INVALID',
        ]);

        // ASSERT
        $response->assertStatus(404);
        $response->assertJsonFragment(['status' => 'error']);
    }

    /**
     * Test: Join returns warning when user already joined
     */
    public function test_join_returns_warning_when_user_already_joined(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');

        // ACT
        $response = $this->postJson('/api/portal/join', [
            'code' => $this->tenant->code,
        ]);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonFragment(['status' => 'warning']);
    }

    /**
     * Test: Join validates required code
     */
    public function test_join_validates_required_code(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');

        // ACT
        $response = $this->postJson('/api/portal/join', []);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    }

    /**
     * Test: Join requires authentication
     */
    public function test_join_requires_authentication(): void
    {
        // ACT
        $response = $this->postJson('/api/portal/join', [
            'code' => $this->tenant->code,
        ]);

        // ASSERT
        $response->assertStatus(401);
    }

    /**
     * Test: Show returns tenant details
     */
    public function test_show_returns_tenant_details(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');

        // ACT
        $response = $this->getJson('/api/portal/' . $this->tenant->id);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'code',
                'slug',
            ],
        ]);
        $response->assertJsonPath('data.id', $this->tenant->id);
    }

    /**
     * Test: Show returns 404 when tenant not found
     */
    public function test_show_returns_404_when_tenant_not_found(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');

        // ACT
        $response = $this->getJson('/api/portal/nonexistent-id');

        // ASSERT
        $response->assertStatus(404);
    }

    /**
     * Test: Show requires authentication
     */
    public function test_show_requires_authentication(): void
    {
        // ACT
        $response = $this->getJson('/api/portal/' . $this->tenant->id);

        // ASSERT
        $response->assertStatus(401);
    }

    /**
     * Test: ShowById returns tenant as collection
     */
    public function test_show_by_id_returns_tenant_as_collection(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');

        // ACT
        $response = $this->getJson('/api/portal/by-id/' . $this->tenant->id);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'code',
                ],
            ],
        ]);
        $this->assertCount(1, $response->json('data'));
    }

    /**
     * Test: ShowById returns all tenants when tenant not found
     */
    public function test_show_by_id_returns_all_tenants_when_not_found(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');

        // ACT
        $response = $this->getJson('/api/portal/by-id/nonexistent-id');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'code',
                ],
            ],
        ]);
        // Should return all user's tenants
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    /**
     * Test: ShowById requires authentication
     */
    public function test_show_by_id_requires_authentication(): void
    {
        // ACT
        $response = $this->getJson('/api/portal/by-id/' . $this->tenant->id);

        // ASSERT
        $response->assertStatus(401);
    }

    /**
     * Test: Update allows super admin to update tenant
     */
    public function test_update_allows_super_admin_to_update_tenant(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');
        $data = [
            'name' => 'Updated Portal Name',
            'code' => $this->tenant->code,
            'company_category_id' => null,
        ];

        // ACT
        $response = $this->postJson('/api/portal/' . $this->tenant->id, $data);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'success']);
        
        // Verify name updated
        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'name' => 'Updated Portal Name',
        ]);
    }

    public function test_update_slug_denies_non_owner(): void
    {
        $anotherUser = User::factory()->create();
        $this->actingAs($anotherUser, 'api');

        // attach user ke tenant tapi bukan owner
        $anotherUser->tenants()->attach($this->tenant->id, [
            'role' => 'super_admin',
            'is_owner' => false,
        ]);

        $response = $this->postJson('/api/portal/' . $this->tenant->id . '/slug', [
            'slug' => 'new-slug-not-owner',
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment(['status' => 'forbidden']);
    }

    public function test_update_slug_applies_and_saves_history_for_owner(): void
    {
        $this->actingAs($this->centralUser, 'api');

        $oldSlug = $this->tenant->slug;
        $newSlug = 'updated-slug-owner';

        $response = $this->postJson('/api/portal/' . $this->tenant->id . '/slug', [
            'slug' => $newSlug,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'success']);

        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'slug' => $newSlug,
        ]);

        $this->assertDatabaseHas('tenant_slug_histories', [
            'tenant_id' => $this->tenant->id,
            'slug' => $oldSlug,
        ]);
    }

    public function test_update_slug_is_blocked_by_cooldown_90_days(): void
    {
        $this->actingAs($this->centralUser, 'api');

        // Set last change ke kemarin agar masih dalam window 90 hari
        $this->tenant->update([
            'slug_changed_at' => now()->subDay(),
        ]);

        $response = $this->postJson('/api/portal/' . $this->tenant->id . '/slug', [
            'slug' => 'blocked-by-cooldown',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['status' => 'warning']);
        $this->assertDatabaseMissing('tenants', [
            'id' => $this->tenant->id,
            'slug' => 'blocked-by-cooldown',
        ]);
    }

    public function test_update_slug_allows_change_when_cooldown_disabled_via_config(): void
    {
        $previous = (int) config('custom.portal_slug_change_cooldown_days', 30);
        config(['custom.portal_slug_change_cooldown_days' => 0]);

        $this->actingAs($this->centralUser, 'api');

        $this->tenant->update([
            'slug_changed_at' => now()->subDay(),
        ]);

        $response = $this->postJson('/api/portal/' . $this->tenant->id . '/slug', [
            'slug' => 'allowed-without-cooldown',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'success']);

        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'slug' => 'allowed-without-cooldown',
        ]);

        config(['custom.portal_slug_change_cooldown_days' => $previous]);
    }

    public function test_request_with_old_slug_redirects_to_current_slug(): void
    {
        $tenant = Tenant::factory()->create([
            'slug' => 'new-slug-redirect',
            'enable_slug_history_redirect' => true,
        ]);

        TenantSlugHistory::create([
            'tenant_id' => $tenant->id,
            'slug' => 'old-slug-redirect',
        ]);

        $response = $this->get('/old-slug-redirect');

        $response->assertStatus(302);
        $response->assertRedirect('/new-slug-redirect');
    }

    /**
     * Test: Update portal dengan field lengkap (kategori, tentang kami, employee range, dan social links)
     */
    public function test_update_updates_full_portal_fields(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');

        // Buat kategori perusahaan
        $category = \App\Models\CompanyCategory::factory()->create();

        $data = [
            'name' => 'Updated Portal Full',
            'code' => $this->tenant->code,
            'company_category_id' => $category->id,
            'company_values' => '<p>Nilai perusahaan baru</p>',
            'employee_range_start' => 11,
            'employee_range_end' => 50,
            'linkedin' => 'https://www.linkedin.com/company/full-update',
            'instagram' => 'https://www.instagram.com/fullupdate',
            'website' => 'https://fullupdate.example.com',
        ];

        // ACT
        $response = $this->postJson('/api/portal/' . $this->tenant->id, $data);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'success']);

        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'name' => 'Updated Portal Full',
            'company_category_id' => $category->id,
            'company_values' => '<p>Nilai perusahaan baru</p>',
            'employee_range_start' => 11,
            'employee_range_end' => 50,
            'linkedin' => 'https://www.linkedin.com/company/full-update',
            'instagram' => 'https://www.instagram.com/fullupdate',
            'website' => 'https://fullupdate.example.com',
        ]);
    }

    /**
     * Test: Update dapat mengosongkan social links ketika field dikirim sebagai string kosong
     */
    public function test_update_can_clear_social_links(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');

        // Set initial social links pada tenant
        $this->tenant->update([
            'linkedin' => 'https://www.linkedin.com/company/initial',
            'instagram' => 'https://www.instagram.com/initial',
            'website' => 'https://initial.example.com',
        ]);

        $data = [
            // Gunakan nama & code yang sama agar tidak kena unique validation
            'name' => $this->tenant->name,
            'code' => $this->tenant->code,
            'linkedin' => '',
            'instagram' => '',
            'website' => '',
        ];

        // ACT
        $response = $this->postJson('/api/portal/' . $this->tenant->id, $data);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'success']);

        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'linkedin' => null,
            'instagram' => null,
            'website' => null,
        ]);
    }

    /**
     * Test: Update fails when name already used by another tenant
     * 
     * Test ini akan hit validation error di TenantRequest
     * karena Rule::unique('tenants', 'name') akan mendeteksi duplicate.
     */
    public function test_update_fails_when_name_already_used_by_another_tenant(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');
        
        // Buat tenant kedua dengan nama berbeda
        $anotherTenant = Tenant::factory()->create([
            'name' => 'Another Company Name',
            'owner_id' => $this->centralUser->id,
        ]);

        // ACT - Coba update tenant pertama dengan nama tenant kedua
        $response = $this->postJson('/api/portal/' . $this->tenant->id, [
            'name' => 'Another Company Name', // ← Already used by anotherTenant
            'code' => $this->tenant->code,
            'company_category_id' => null,
        ]);

        // ASSERT - Validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
        
        // Verify tenant pertama tidak berubah
        $this->tenant->refresh();
        $this->assertNotEquals('Another Company Name', $this->tenant->name);
    }

    /**
     * Test: Update denies recruiter from updating tenant
     */
    public function test_update_denies_recruiter_from_updating_tenant(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUserRecruiter, 'api');
        $data = [
            'name' => 'Updated by Recruiter',
            'code' => $this->tenant->code,
            'company_category_id' => null,
        ];

        // ACT
        $response = $this->postJson('/api/portal/' . $this->tenant->id, $data);

        // ASSERT
        $response->assertStatus(403);
    }

    /**
     * Test: Update requires authentication
     */
    public function test_update_requires_authentication(): void
    {
        // ACT
        $response = $this->postJson('/api/portal/' . $this->tenant->id, [
            'name' => 'Test',
        ]);

        // ASSERT
        $response->assertStatus(401);
    }

    /**
     * Test: Destroy deletes tenant
     */
    public function test_destroy_deletes_tenant(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');
        $tenantToDelete = Tenant::factory()->create();

        // ACT
        $response = $this->deleteJson('/api/portal/' . $tenantToDelete->id);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'success']);
        
        $this->assertDatabaseMissing('tenants', [
            'id' => $tenantToDelete->id,
        ]);
    }

    /**
     * Test: Destroy requires authentication
     */
    public function test_destroy_requires_authentication(): void
    {
        // ACT
        $response = $this->deleteJson('/api/portal/' . $this->tenant->id);

        // ASSERT
        $response->assertStatus(401);
    }

    /**
     * Test: GenerateCode returns unique code
     */
    public function test_generate_code_returns_unique_code(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');

        // ACT
        $response = $this->getJson('/api/portal/generate-code');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'code',
        ]);
        $response->assertJsonFragment(['status' => 'success']);
        
        // Verify code format (should be 6-10 characters)
        $code = $response->json('code');
        $this->assertGreaterThanOrEqual(6, strlen($code));
        $this->assertLessThanOrEqual(10, strlen($code));
    }

    /**
     * Test: GenerateCode requires authentication
     */
    public function test_generate_code_requires_authentication(): void
    {
        // ACT
        $response = $this->getJson('/api/portal/generate-code');

        // ASSERT
        $response->assertStatus(401);
    }

    /**
     * Test: CheckNusaworkIntegration returns true when owner is integrated
     */
    public function test_check_nusawork_integration_returns_true_when_owner_is_integrated(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');
        
        // Set owner tenant user sebagai sudah terintegrasi dengan Nusawork
        // Pastikan pivot table memiliki is_owner = true
        $this->centralUser->tenants()->updateExistingPivot($this->tenant->id, [
            'is_owner' => true,
            'is_nusawork_integrated' => true,
            'nusawork_integrated_at' => now(),
        ]);

        // ACT
        $response = $this->getJson("/api/portal/{$this->tenant->id}/nusawork-integration");

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'is_nusawork_integrated',
            'nusawork_integrated_at',
        ]);
        $response->assertJsonFragment(['status' => 'success']);
        $response->assertJsonPath('is_nusawork_integrated', true);
        $this->assertNotNull($response->json('nusawork_integrated_at'));
    }

    /**
     * Test: CheckNusaworkIntegration returns false when owner is not integrated
     */
    public function test_check_nusawork_integration_returns_false_when_owner_is_not_integrated(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');
        
        // Pastikan owner tidak terintegrasi dengan Nusawork
        // Set is_owner = true untuk memastikan owner terdeteksi
        $this->centralUser->tenants()->updateExistingPivot($this->tenant->id, [
            'is_owner' => true,
            'is_nusawork_integrated' => false,
            'nusawork_integrated_at' => null,
        ]);

        // ACT
        $response = $this->getJson("/api/portal/{$this->tenant->id}/nusawork-integration");

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'success']);
        $response->assertJsonPath('is_nusawork_integrated', false);
        $this->assertNull($response->json('nusawork_integrated_at'));
    }

    /**
     * Test: CheckNusaworkIntegration returns null when tenant not found
     */
    public function test_check_nusawork_integration_returns_null_when_tenant_not_found(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');

        // ACT
        $response = $this->getJson('/api/portal/nonexistent-tenant-id/nusawork-integration');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'success']);
        $response->assertJsonPath('is_nusawork_integrated', null);
        $this->assertNull($response->json('nusawork_integrated_at'));
    }

    /**
     * Test: CheckNusaworkIntegration returns null when owner not found
     */
    public function test_check_nusawork_integration_returns_null_when_owner_not_found(): void
    {
        // ARRANGE
        $this->actingAs($this->centralUser, 'api');
        
        // Buat tenant tanpa owner (hapus semua tenant_users dengan is_owner = true)
        $tenantWithoutOwner = Tenant::factory()->create();
        // Tidak attach user apapun sebagai owner

        // ACT
        $response = $this->getJson("/api/portal/{$tenantWithoutOwner->id}/nusawork-integration");

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'success']);
        $response->assertJsonPath('is_nusawork_integrated', null);
        $this->assertNull($response->json('nusawork_integrated_at'));
    }

    /**
     * Test: CheckNusaworkIntegration requires authentication
     */
    public function test_check_nusawork_integration_requires_authentication(): void
    {
        // ACT
        $response = $this->getJson("/api/portal/{$this->tenant->id}/nusawork-integration");

        // ASSERT
        $response->assertStatus(401);
    }

    /**
     * Test: Recruiter join via invitation code creates login audit log
     */
    public function test_recruiter_join_via_code_creates_login_audit_log(): void
    {
        // ARRANGE
        $newUser = User::factory()->create([
            'email' => 'newrecruiter@example.com',
            'name' => 'New Recruiter',
        ]);

        // Create invitation in tenant context
        $this->tenant->run(function () use ($newUser) {
            \App\Models\Tenant\RecruiterInvitation::create([
                'email' => $newUser->email,
                'code' => $this->tenant->code,
                'invited_by_email' => $this->centralUser->email,
                'expires_at' => now()->addDays(7),
            ]);
        });

        // ACT - Join tenant via invitation code
        $response = $this->actingAs($newUser, 'api')
            ->postJson('/api/portal/join', [
                'code' => $this->tenant->code,
            ]);

        // ASSERT
        $response->assertStatus(200);

        // Check audit log exists in tenant context
        $this->tenant->run(function () use ($newUser) {
            $audit = \OwenIt\Auditing\Models\Audit::where('event', 'login')
                ->where('tags', 'login')
                ->latest()
                ->first();

            $this->assertNotNull($audit, 'Login audit log should be created');
            $this->assertEquals('login', $audit->event);

            // Check new_values has user details
            $this->assertArrayHasKey('role', $audit->new_values);
            $this->assertArrayHasKey('name', $audit->new_values);
            $this->assertArrayHasKey('email', $audit->new_values);
            $this->assertArrayHasKey('last_login_ip', $audit->new_values);
            $this->assertArrayHasKey('last_login_at', $audit->new_values);

            // Check is_new_admin flag for new admin (ex-recruiter)
            $this->assertArrayHasKey('is_new_admin', $audit->new_values);
            $this->assertTrue($audit->new_values['is_new_admin']);
        });
    }

    /**
     * Test: Create portal creates login audit log for owner
     */
    public function test_create_portal_creates_login_audit_log_for_owner(): void
    {
        // ARRANGE
        $owner = User::factory()->create([
            'email' => 'owner@example.com',
            'name' => 'Portal Owner',
        ]);

        // ACT - Create new portal
        $response = $this->actingAs($owner, 'api')
            ->postJson('/api/portal', [
                'name' => 'New Test Portal',
                'code' => 'NEWPORTAL',
                'slug' => 'new-test-portal',
            ]);

        // ASSERT
        $response->assertStatus(200);
        
        // Debug response if failed
        if ($response->json('status') !== 'success') {
            dump($response->json());
        }
        
        $response->assertJsonPath('status', 'success');

        // Get created tenant
        $createdTenant = Tenant::where('code', 'NEWPORTAL')->first();
        
        // If tenant not found, check if portal was created with different code
        if (!$createdTenant) {
            $createdTenant = Tenant::where('owner_id', $owner->id)
                ->where('name', 'New Test Portal')
                ->first();
        }
        
        $this->assertNotNull($createdTenant, 'Tenant should be created');

        // Check audit log exists in tenant context
        $createdTenant->run(function () use ($owner) {
            $audit = \OwenIt\Auditing\Models\Audit::where('event', 'login')
                ->where('tags', 'login')
                ->latest()
                ->first();

            $this->assertNotNull($audit, 'Login audit log should be created for portal owner');
            $this->assertEquals('login', $audit->event);

            // Check new_values has user details
            $this->assertArrayHasKey('role', $audit->new_values);
            $this->assertEquals('super_admin', $audit->new_values['role']);
            $this->assertArrayHasKey('name', $audit->new_values);
            $this->assertArrayHasKey('email', $audit->new_values);
            $this->assertArrayHasKey('last_login_ip', $audit->new_values);
            $this->assertArrayHasKey('last_login_at', $audit->new_values);

            // Owner should NOT have is_new_admin flag
            $this->assertArrayNotHasKey('is_new_admin', $audit->new_values);
        });
    }
}
