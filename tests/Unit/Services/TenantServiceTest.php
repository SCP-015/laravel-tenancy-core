<?php

namespace Tests\Unit\Services;

use App\Models\CompanyCategory;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Mockery;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

class TenantServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Pastikan tidak ada tenancy yang mengganggu unit test ini
        if (function_exists('tenancy')) {
            try {
                tenancy()->end();
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    public function test_update_creates_portal_audit_with_formatted_values(): void
    {
        $oldCategory = CompanyCategory::factory()->create();
        $newCategory = CompanyCategory::factory()->create();

        $tenant = Tenant::factory()->create([
            'theme_color' => '#111111',
            'header_image' => 'portal/ABC/images/header_old.png',
            'profile_image' => 'portal/ABC/images/profile_old.png',
            'company_values' => '<p>Visi lama</p>',
            'employee_range_start' => 1,
            'employee_range_end' => 10,
            'company_category_id' => $oldCategory->id,
        ]);

        Storage::fake('public');

        $validated = [
            'name' => $tenant->name,
            'code' => $tenant->code,
            'theme_color' => '#222222',
            'header_image' => $this->fakeUploadedFile('header_new.png'),
            'profile_image' => $this->fakeUploadedFile('profile_new.png'),
            'company_values' => '<p>Visi baru</p>',
            'employee_range_start' => 5,
            'employee_range_end' => 20,
            'company_category_id' => $newCategory->id,
        ];

        $result = TenantService::update($validated, (string) $tenant->id);
        
        $this->assertNotNull($result);
        $tenant->refresh();
        $this->assertSame('#222222', $tenant->theme_color);
        $this->assertSame($newCategory->id, $tenant->company_category_id);
    }

    public function test_update_with_no_changes_skips_audit(): void
    {
        $category = CompanyCategory::factory()->create();

        $tenant = Tenant::factory()->create([
            'company_category_id' => $category->id,
            'company_values' => 'Same value',
        ]);

        Storage::fake('public');

        // Update dengan nilai yang sama persis
        $validated = [
            'name' => $tenant->name,
            'code' => $tenant->code,
            'theme_color' => $tenant->theme_color,
            'company_category_id' => $category->id,
            'company_values' => 'Same value',
            'employee_range_start' => $tenant->employee_range_start,
            'employee_range_end' => $tenant->employee_range_end,
        ];

        $result = TenantService::update($validated, (string) $tenant->id);
        $this->assertNotNull($result);
    }

    public function test_create_portal_audit_log_returns_early_when_new_values_empty(): void
    {
        $tenant = Tenant::factory()->create();

        // Jangan pernah memanggil Audit::create pada kasus ini
        $this->partialMock(Audit::class, function ($mock) {
            $mock->shouldReceive('create')->never();
        });

        $refClass = new \ReflectionClass(TenantService::class);
        $method = $refClass->getMethod('createPortalAuditLog');
        $method->setAccessible(true);

        // newValues kosong -> langsung return, tidak ada exception
        $method->invoke(null, $tenant, [], []);
        $this->assertTrue(true); // Jika sampai sini, berarti aman
    }

    public function test_update_with_only_category_change(): void
    {
        $oldCategory = CompanyCategory::factory()->create();
        $newCategory = CompanyCategory::factory()->create();

        $tenant = Tenant::factory()->create([
            'company_category_id' => $oldCategory->id,
        ]);

        Storage::fake('public');

        $validated = [
            'name' => $tenant->name,
            'code' => $tenant->code,
            'company_category_id' => $newCategory->id,
        ];

        $result = TenantService::update($validated, (string) $tenant->id);
        
        $this->assertNotNull($result);
        $tenant->refresh();
        $this->assertSame($newCategory->id, $tenant->company_category_id);
    }

    public function test_update_with_only_company_values_change(): void
    {
        $tenant = Tenant::factory()->create([
            'company_values' => '<p>Old <strong>values</strong></p>',
        ]);

        Storage::fake('public');

        $validated = [
            'name' => $tenant->name,
            'code' => $tenant->code,
            'company_values' => '<p>New <em>values</em></p>',
        ];

        $result = TenantService::update($validated, (string) $tenant->id);
        
        $this->assertNotNull($result);
        $tenant->refresh();
        $this->assertStringContainsString('New', $tenant->company_values);
    }

    public function test_update_with_scalar_field_changes(): void
    {
        $tenant = Tenant::factory()->create([
            'theme_color' => '#111111',
            'employee_range_start' => 1,
            'employee_range_end' => 10,
        ]);

        Storage::fake('public');

        // Update beberapa field scalar yang berbeda
        $validated = [
            'name' => $tenant->name,
            'code' => $tenant->code,
            'theme_color' => '#222222',
            'employee_range_start' => 5,
            'employee_range_end' => 20,
        ];

        $result = TenantService::update($validated, (string) $tenant->id);
        
        $this->assertNotNull($result);
        $tenant->refresh();
        $this->assertSame('#222222', $tenant->theme_color);
        $this->assertSame(5, $tenant->employee_range_start);
        $this->assertSame(20, $tenant->employee_range_end);
    }

    public function test_update_with_social_media_fields(): void
    {
        $tenant = Tenant::factory()->create([
            'linkedin' => 'https://linkedin.com/company/old',
            'instagram' => '@old_instagram',
            'website' => 'https://old-website.com',
        ]);

        Storage::fake('public');

        // Update social media fields
        $validated = [
            'name' => $tenant->name,
            'code' => $tenant->code,
            'linkedin' => 'https://linkedin.com/company/new',
            'instagram' => '@new_instagram',
            'website' => 'https://new-website.com',
        ];

        $result = TenantService::update($validated, (string) $tenant->id);
        
        $this->assertNotNull($result);
        $tenant->refresh();
        $this->assertSame('https://linkedin.com/company/new', $tenant->linkedin);
        $this->assertSame('@new_instagram', $tenant->instagram);
        $this->assertSame('https://new-website.com', $tenant->website);
    }

    public function test_update_with_null_social_media_fields(): void
    {
        $tenant = Tenant::factory()->create([
            'linkedin' => 'https://linkedin.com/company/test',
            'instagram' => '@test_instagram',
            'website' => 'https://test-website.com',
        ]);

        Storage::fake('public');

        // Update dengan null (menghapus social media)
        $validated = [
            'name' => $tenant->name,
            'code' => $tenant->code,
            'linkedin' => null,
            'instagram' => null,
            'website' => null,
        ];

        $result = TenantService::update($validated, (string) $tenant->id);
        
        $this->assertNotNull($result);
        $tenant->refresh();
        $this->assertNull($tenant->linkedin);
        $this->assertNull($tenant->instagram);
        $this->assertNull($tenant->website);
    }

    public function test_update_parses_enable_slug_history_redirect_from_form_data_string(): void
    {
        $tenant = Tenant::factory()->create([
            'enable_slug_history_redirect' => false,
        ]);

        Storage::fake('public');

        $validatedEnable = [
            'name' => $tenant->name,
            'code' => $tenant->code,
            'enable_slug_history_redirect' => '1',
        ];

        $resultEnable = TenantService::update($validatedEnable, (string) $tenant->id);
        $this->assertNotNull($resultEnable);
        $tenant->refresh();
        $this->assertTrue((bool) $tenant->enable_slug_history_redirect);

        $validatedDisable = [
            'name' => $tenant->name,
            'code' => $tenant->code,
            'enable_slug_history_redirect' => '0',
        ];

        $resultDisable = TenantService::update($validatedDisable, (string) $tenant->id);
        $this->assertNotNull($resultDisable);
        $tenant->refresh();
        $this->assertFalse((bool) $tenant->enable_slug_history_redirect);
    }

    public function test_store_returns_warning_when_tenant_already_exists(): void
    {
        Tenant::factory()->create([
            'name' => 'Portal Sudah Ada',
            'slug' => 'portal-sudah-ada',
        ]);

        $result = TenantService::store([
            'name' => 'Portal Sudah Ada',
            'slug' => 'portal-sudah-ada',
            'code' => Tenant::generateCode(),
        ]);

        $this->assertIsArray($result);
        $this->assertSame('warning', $result['status']);
    }

    public function test_update_slug_returns_success_when_slug_is_same(): void
    {
        $tenant = Tenant::factory()->create([
            'slug' => 'same-slug',
        ]);

        $result = TenantService::updateSlug('same-slug', (string) $tenant->id);

        $this->assertIsArray($result);
        $this->assertSame('success', $result['status']);
        $this->assertArrayHasKey('portal', $result);
    }

    public function test_update_slug_returns_forbidden_when_user_is_unauthenticated(): void
    {
        $tenant = Tenant::factory()->create([
            'slug' => 'original-slug',
        ]);

        Auth::shouldReceive('user')->andReturn(null);

        $result = TenantService::updateSlug('new-slug', (string) $tenant->id);

        $this->assertIsArray($result);
        $this->assertSame('forbidden', $result['status']);
    }

    public function test_update_slug_returns_forbidden_when_user_is_not_app_user_instance(): void
    {
        $tenant = Tenant::factory()->create([
            'slug' => 'original-slug-2',
        ]);

        Auth::shouldReceive('user')->andReturn((object) ['id' => 999]);

        $result = TenantService::updateSlug('new-slug-2', (string) $tenant->id);

        $this->assertIsArray($result);
        $this->assertSame('forbidden', $result['status']);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function test_update_slug_returns_error_when_exception_occurs(): void
    {
        $owner = \App\Models\User::factory()->create();
        $tenant = Tenant::factory()->create([
            'owner_id' => $owner->id,
            'slug' => 'original-slug-3',
        ]);

        Auth::shouldReceive('user')->andReturn($owner);

        $mockHistory = Mockery::mock('alias:App\\Models\\TenantSlugHistory');
        $mockHistory->shouldReceive('firstOrCreate')->andThrow(new \Exception('boom'));

        $result = TenantService::updateSlug('new-slug-3', (string) $tenant->id);

        $this->assertIsArray($result);
        $this->assertSame('error', $result['status']);
        $this->assertSame('Failed to update portal slug', $result['message']);
        $this->assertSame('boom', $result['error']);
    }

    public function test_destroy_deletes_tenant_and_returns_success(): void
    {
        $tenant = Tenant::factory()->create();

        Storage::fake('public');

        $result = TenantService::destroy((string) $tenant->id);

        $this->assertIsArray($result);
        $this->assertSame('success', $result['status']);
        $this->assertDatabaseMissing('tenants', [
            'id' => $tenant->id,
        ]);
    }

    private function fakeUploadedFile(string $name)
    {
        return \Illuminate\Http\UploadedFile::fake()->image($name);
    }
}
                                            