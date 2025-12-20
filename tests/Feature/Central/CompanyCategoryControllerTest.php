<?php

namespace Tests\Feature\Central;

use App\Models\CompanyCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite untuk CompanyCategoryController
 * Target: 100% coverage untuk semua method controller
 */
class CompanyCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $authenticatedUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat user untuk authentication
        $this->authenticatedUser = User::factory()->create();
    }

    /**
     * Test: Index returns list of company categories successfully
     */
    public function test_index_returns_company_categories_list_successfully(): void
    {
        // ARRANGE
        $this->actingAs($this->authenticatedUser, 'api');
        
        CompanyCategory::query()->delete();
        for ($i = 1; $i <= 5; $i++) {
            CompanyCategory::create(['name' => 'Category Index ' . uniqid(), 'is_active' => true]);
        }

        // ACT
        $response = $this->getJson('/api/company-categories');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'is_active',
                ],
            ],
        ]);
        $response->assertJsonCount(5, 'data');
    }

    /**
     * Test: Index returns empty array when no categories exist
     */
    public function test_index_returns_empty_array_when_no_categories(): void
    {
        // ARRANGE
        $this->actingAs($this->authenticatedUser, 'api');
        
        CompanyCategory::query()->delete();

        // ACT
        $response = $this->getJson('/api/company-categories');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }

    /**
     * Test: Index fails without authentication
     */
    public function test_index_fails_without_authentication(): void
    {
        // ACT
        $response = $this->getJson('/api/company-categories');

        // ASSERT
        $response->assertUnauthorized();
    }

    /**
     * Test: Index returns categories ordered by ID
     */
    public function test_index_returns_categories_ordered_by_id(): void
    {
        // ARRANGE
        $this->actingAs($this->authenticatedUser, 'api');
        
        CompanyCategory::query()->delete();
        
        $category1 = CompanyCategory::create(['name' => 'Category AAA ' . uniqid(), 'is_active' => true]);
        $category2 = CompanyCategory::create(['name' => 'Category BBB ' . uniqid(), 'is_active' => true]);
        $category3 = CompanyCategory::create(['name' => 'Category CCC ' . uniqid(), 'is_active' => true]);

        // ACT
        $response = $this->getJson('/api/company-categories');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $category1->id);
        $response->assertJsonPath('data.1.id', $category2->id);
        $response->assertJsonPath('data.2.id', $category3->id);
    }

    /**
     * Test: Store creates company category successfully
     */
    public function test_store_creates_company_category_successfully(): void
    {
        // ARRANGE
        $this->actingAs($this->authenticatedUser, 'api');

        $payload = [
            'name' => 'Teknologi Informasi',
            'description' => 'Perusahaan yang bergerak di bidang IT',
            'is_active' => true,
        ];

        // ACT
        $response = $this->postJson('/api/company-categories', $payload);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'code',
            'title',
            'message',
            'data' => [
                'id',
                'name',
                'slug',
                'description',
                'is_active',
            ],
        ]);
        $response->assertJsonPath('data.name', 'Teknologi Informasi');
        $response->assertJsonPath('data.slug', 'teknologi-informasi');
        $response->assertJsonPath('message', __('Company category created successfully'));

        $this->assertDatabaseHas('company_categories', [
            'name' => 'Teknologi Informasi',
            'slug' => 'teknologi-informasi',
            'description' => 'Perusahaan yang bergerak di bidang IT',
            'is_active' => true,
        ]);
    }

    /**
     * Test: Store creates company category without optional fields
     */
    public function test_store_creates_company_category_without_optional_fields(): void
    {
        // ARRANGE
        $this->actingAs($this->authenticatedUser, 'api');

        $payload = [
            'name' => 'Finance Sector',
        ];

        // ACT
        $response = $this->postJson('/api/company-categories', $payload);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Finance Sector');
        
        $this->assertDatabaseHas('company_categories', [
            'name' => 'Finance Sector',
            'slug' => 'finance-sector',
        ]);
    }

    /**
     * Test: Store validates required name field
     */
    public function test_store_validates_required_name_field(): void
    {
        // ARRANGE
        $this->actingAs($this->authenticatedUser, 'api');

        $payload = [
            'description' => 'Some description',
        ];

        // ACT
        $response = $this->postJson('/api/company-categories', $payload);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Test: Store validates unique name field
     */
    public function test_store_validates_unique_name_field(): void
    {
        // ARRANGE
        $this->actingAs($this->authenticatedUser, 'api');

        $uniqueName = 'Technology Unique Test ' . uniqid();
        CompanyCategory::create(['name' => $uniqueName, 'is_active' => true]);

        $payload = [
            'name' => $uniqueName,
            'description' => 'Tech companies',
        ];

        // ACT
        $response = $this->postJson('/api/company-categories', $payload);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Test: Store validates name max length
     */
    public function test_store_validates_name_max_length(): void
    {
        // ARRANGE
        $this->actingAs($this->authenticatedUser, 'api');

        $payload = [
            'name' => str_repeat('a', 256), // Exceeds 255 chars
            'description' => 'Description',
        ];

        // ACT
        $response = $this->postJson('/api/company-categories', $payload);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Test: Store fails without authentication
     */
    public function test_store_fails_without_authentication(): void
    {
        // ARRANGE
        $payload = [
            'name' => 'Technology',
            'description' => 'Tech companies',
        ];

        // ACT
        $response = $this->postJson('/api/company-categories', $payload);

        // ASSERT
        $response->assertUnauthorized();
    }

    /**
     * Test: Show returns company category details
     */
    public function test_show_returns_company_category_details(): void
    {
        // ARRANGE
        $this->actingAs($this->authenticatedUser, 'api');

        $category = CompanyCategory::create([
            'name' => 'Healthcare Industry ' . uniqid(),
            'description' => 'Healthcare industry',
            'is_active' => true,
        ]);

        // ACT
        $response = $this->getJson("/api/company-categories/{$category->id}");

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $category->id);
        $this->assertTrue(str_starts_with($response->json('data.name'), 'Healthcare Industry'));
        $response->assertJsonPath('data.description', 'Healthcare industry');
    }

    /**
     * Test: Show returns 404 for non-existent company category
     */
    public function test_show_returns_404_for_nonexistent_company_category(): void
    {
        // ARRANGE
        $this->actingAs($this->authenticatedUser, 'api');

        // ACT
        $response = $this->getJson('/api/company-categories/99999');

        // ASSERT
        $response->assertStatus(404);
    }

    /**
     * Test: Show fails without authentication
     */
    public function test_show_fails_without_authentication(): void
    {
        // ARRANGE
        $category = CompanyCategory::create(['name' => 'Test Show Auth ' . uniqid(), 'is_active' => true]);

        // ACT
        $response = $this->getJson("/api/company-categories/{$category->id}");

        // ASSERT
        $response->assertUnauthorized();
    }

    /**
     * Test: Update updates company category successfully
     */
    public function test_update_updates_company_category_successfully(): void
    {
        // ARRANGE
        $this->actingAs($this->authenticatedUser, 'api');

        $oldName = 'Old Name Update Test ' . uniqid();
        $newName = 'New Name Update Test ' . uniqid();
        $category = CompanyCategory::create([
            'name' => $oldName,
            'description' => 'Old description',
            'is_active' => true,
        ]);

        $payload = [
            'name' => $newName,
            'description' => 'New description',
            'is_active' => false,
        ];

        // ACT
        $response = $this->putJson("/api/company-categories/{$category->id}", $payload);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonPath('data.name', $newName);
        $response->assertJsonPath('data.description', 'New description');
        $response->assertJsonPath('data.is_active', false);
        $response->assertJsonPath('message', __('Company category updated successfully'));

        $this->assertDatabaseHas('company_categories', [
            'id' => $category->id,
            'name' => $newName,
            'description' => 'New description',
            'is_active' => false,
        ]);
    }

    /**
     * Test: Update can keep same name for same category
     */
    public function test_update_can_keep_same_name_for_same_category(): void
    {
        // ARRANGE
        $this->actingAs($this->authenticatedUser, 'api');

        $name = 'Technology Keep Name ' . uniqid();
        $category = CompanyCategory::create([
            'name' => $name,
            'description' => 'Tech companies',
            'is_active' => true,
        ]);

        $payload = [
            'name' => $name, // Same name
            'description' => 'Updated description',
        ];

        // ACT
        $response = $this->putJson("/api/company-categories/{$category->id}", $payload);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonPath('data.name', $name);
        $response->assertJsonPath('data.description', 'Updated description');
    }

    /**
     * Test: Update validates unique name for different category
     */
    public function test_update_validates_unique_name_for_different_category(): void
    {
        // ARRANGE
        $this->actingAs($this->authenticatedUser, 'api');

        $existingName = 'Existing Name Different ' . uniqid();
        $originalName = 'Original Name Different ' . uniqid();
        $existingCategory = CompanyCategory::create(['name' => $existingName, 'is_active' => true]);
        $categoryToUpdate = CompanyCategory::create(['name' => $originalName, 'is_active' => true]);

        $payload = [
            'name' => $existingName, // Try to use existing category's name
        ];

        // ACT
        $response = $this->putJson("/api/company-categories/{$categoryToUpdate->id}", $payload);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Test: Update validates required name field
     */
    public function test_update_validates_required_name_field(): void
    {
        // ARRANGE
        $this->actingAs($this->authenticatedUser, 'api');

        $category = CompanyCategory::create(['name' => 'Test Required Name ' . uniqid(), 'is_active' => true]);

        $payload = [
            'name' => '',
            'description' => 'Updated description',
        ];

        // ACT
        $response = $this->putJson("/api/company-categories/{$category->id}", $payload);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Test: Update validates name max length
     */
    public function test_update_validates_name_max_length(): void
    {
        // ARRANGE
        $this->actingAs($this->authenticatedUser, 'api');

        $category = CompanyCategory::create(['name' => 'Test Max Length ' . uniqid(), 'is_active' => true]);

        $payload = [
            'name' => str_repeat('a', 256), // Exceeds 255 chars
        ];

        // ACT
        $response = $this->putJson("/api/company-categories/{$category->id}", $payload);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Test: Update returns 404 for non-existent company category
     */
    public function test_update_returns_404_for_nonexistent_company_category(): void
    {
        // ARRANGE
        $this->actingAs($this->authenticatedUser, 'api');

        $payload = [
            'name' => 'New Name',
        ];

        // ACT
        $response = $this->putJson('/api/company-categories/99999', $payload);

        // ASSERT
        $response->assertStatus(404);
    }

    /**
     * Test: Update fails without authentication
     */
    public function test_update_fails_without_authentication(): void
    {
        // ARRANGE
        $category = CompanyCategory::create(['name' => 'Test Update Auth ' . uniqid(), 'is_active' => true]);

        $payload = [
            'name' => 'Updated Name',
        ];

        // ACT
        $response = $this->putJson("/api/company-categories/{$category->id}", $payload);

        // ASSERT
        $response->assertUnauthorized();
    }

    /**
     * Test: Update only changes provided fields
     */
    public function test_update_only_changes_provided_fields(): void
    {
        // ARRANGE
        $this->actingAs($this->authenticatedUser, 'api');

        $name = 'Original Name Partial ' . uniqid();
        $category = CompanyCategory::create([
            'name' => $name,
            'description' => 'Original description',
            'is_active' => true,
        ]);

        $payload = [
            'name' => $name,
            'description' => 'Updated description only',
            // is_active not provided
        ];

        // ACT
        $response = $this->putJson("/api/company-categories/{$category->id}", $payload);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonPath('data.description', 'Updated description only');
        $response->assertJsonPath('data.is_active', true); // Should remain unchanged
    }

    /**
     * Test: Store accepts boolean type for is_active
     */
    public function test_store_accepts_boolean_type_for_is_active(): void
    {
        // ARRANGE
        $this->actingAs($this->authenticatedUser, 'api');

        $payload = [
            'name' => 'Test Category Active',
            'is_active' => true,
        ];

        // ACT
        $response = $this->postJson('/api/company-categories', $payload);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonPath('data.is_active', true);
    }

    /**
     * Test: Update accepts boolean type for is_active
     */
    public function test_update_accepts_boolean_type_for_is_active(): void
    {
        // ARRANGE
        $this->actingAs($this->authenticatedUser, 'api');

        $uniqueName = 'Test Boolean Update ' . uniqid();
        $category = CompanyCategory::create(['name' => $uniqueName, 'is_active' => true]);

        $payload = [
            'name' => $uniqueName,
            'is_active' => false,
        ];

        // ACT
        $response = $this->putJson("/api/company-categories/{$category->id}", $payload);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonPath('data.is_active', false);
    }
}
