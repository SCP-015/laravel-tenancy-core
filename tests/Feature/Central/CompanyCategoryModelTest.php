<?php

namespace Tests\Feature\Central;

use App\Models\CompanyCategory;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test untuk CompanyCategory Model
 * 
 * Coverage target:
 * - Line 30: Slug auto-generation in saving event
 * - Line 40: tenants() hasMany relation
 */
class CompanyCategoryModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Slug otomatis dibuat dari name saat saving
     * 
     * Cover line 30: $category->slug = Str::slug($category->name);
     */
    public function test_slug_is_automatically_generated_from_name_on_save(): void
    {
        // ARRANGE & ACT - Buat category tanpa slug
        $category = CompanyCategory::create([
            'name' => 'Information Technology',
            'description' => 'IT companies',
            'is_active' => true,
        ]);

        // ASSERT - Slug harus auto-generated
        $this->assertNotNull($category->slug);
        $this->assertEquals('information-technology', $category->slug);
    }

    /**
     * Test: Slug diupdate ketika name berubah
     */
    public function test_slug_is_updated_when_name_changes(): void
    {
        // ARRANGE
        $category = CompanyCategory::create([
            'name' => 'Technology',
            'description' => 'Tech companies',
            'is_active' => true,
        ]);
        
        $this->assertEquals('technology', $category->slug);

        // ACT - Update name
        $category->name = 'Information Technology';
        $category->save();

        // ASSERT - Slug harus ikut berubah
        $this->assertEquals('information-technology', $category->slug);
    }

    /**
     * Test: Slug handles special characters correctly
     */
    public function test_slug_handles_special_characters(): void
    {
        // ARRANGE & ACT
        $category = CompanyCategory::create([
            'name' => 'E-Commerce & Retail',
            'description' => 'Online stores',
            'is_active' => true,
        ]);

        // ASSERT
        $this->assertEquals('e-commerce-retail', $category->slug);
    }

    /**
     * Test: tenants() relation mengembalikan tenants yang benar
     * 
     * Cover line 40: return $this->hasMany(Tenant::class, 'company_category_id');
     */
    public function test_tenants_relation_returns_correct_tenants(): void
    {
        // ARRANGE
        $category = CompanyCategory::create([
            'name' => 'Technology',
            'description' => 'Tech companies',
            'is_active' => true,
        ]);
        
        // Use unique IDs to avoid database conflicts
        $uniqueId1 = 'test-tech-' . uniqid();
        $uniqueId2 = 'test-tech-' . uniqid();
        $uniqueId3 = 'test-fin-' . uniqid();
        
        // Buat 2 tenants dengan category ini
        $tenant1 = Tenant::create([
            'id' => $uniqueId1,
            'name' => 'Tech Corp 1',
            'code' => 'TECH1-' . uniqid(),
            'company_category_id' => $category->id,
        ]);
        
        $tenant2 = Tenant::create([
            'id' => $uniqueId2,
            'name' => 'Tech Corp 2',
            'code' => 'TECH2-' . uniqid(),
            'company_category_id' => $category->id,
        ]);
        
        // Buat tenant dengan category lain (tidak boleh muncul)
        $otherCategory = CompanyCategory::create([
            'name' => 'Finance',
            'description' => 'Finance companies',
            'is_active' => true,
        ]);
        
        Tenant::create([
            'id' => $uniqueId3,
            'name' => 'Finance Corp',
            'code' => 'FIN1-' . uniqid(),
            'company_category_id' => $otherCategory->id,
        ]);

        // ACT
        $tenants = $category->tenants;

        // ASSERT
        $this->assertCount(2, $tenants);
        
        $tenantIds = $tenants->pluck('id')->toArray();
        $this->assertContains($tenant1->id, $tenantIds);
        $this->assertContains($tenant2->id, $tenantIds);
        $this->assertNotContains($uniqueId3, $tenantIds);
    }

    /**
     * Test: tenants() relation returns empty when no tenants
     */
    public function test_tenants_relation_returns_empty_when_no_tenants(): void
    {
        // ARRANGE
        $category = CompanyCategory::create([
            'name' => 'New Category',
            'description' => 'No tenants yet',
            'is_active' => true,
        ]);

        // ACT
        $tenants = $category->tenants;

        // ASSERT
        $this->assertCount(0, $tenants);
    }
}
