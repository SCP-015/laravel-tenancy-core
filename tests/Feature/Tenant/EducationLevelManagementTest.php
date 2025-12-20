<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant\EducationLevel;
use Tests\Feature\TenantTestCase;

/**
 * ======================================================================
 * Test untuk flow: Education Level Management (Master Data)
 * ======================================================================
 *
 * Target Coverage:
 * - EducationLevelController: 0% → 80%+
 * - EducationLevelService: 0% → 80%+
 *
 * Cara menjalankan test ini:
 * php artisan test tests/Feature/Tenant/EducationLevelManagementTest.php
 * ======================================================================
 */
class EducationLevelManagementTest extends TenantTestCase
{
    // ===================================================================================
    // TEST: LIST EDUCATION LEVELS (GET /api/settings/education-levels)
    // ===================================================================================

    public function test_can_list_all_education_levels(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        EducationLevel::factory()->count(5)->create();

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/education-levels");

        // ASSERT
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'index'],
            ],
        ]);
        $this->assertGreaterThanOrEqual(5, count($response->json('data')));
    }

    public function test_education_levels_ordered_by_index(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        EducationLevel::factory()->create(['name' => 'High Level', 'index' => 30]);
        EducationLevel::factory()->create(['name' => 'Low Level', 'index' => 10]);
        EducationLevel::factory()->create(['name' => 'Mid Level', 'index' => 20]);

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/education-levels");

        // ASSERT
        $response->assertOk();
        $data = $response->json('data');
        
        // Verify ordering by index (ascending)
        $indexes = array_column($data, 'index');
        $sortedIndexes = $indexes;
        sort($sortedIndexes);
        $this->assertEquals($sortedIndexes, $indexes);
    }

    public function test_fails_to_list_if_unauthenticated(): void
    {
        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/education-levels");

        // ASSERT
        $response->assertUnauthorized();
    }

    // ===================================================================================
    // TEST: CREATE EDUCATION LEVEL (POST /api/settings/education-levels)
    // ===================================================================================

    public function test_can_create_education_level(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $payload = [
            'name' => 'Master Degree',
            'index' => 10,
        ];

        // ACT
        $response = $this->postJson("/{$this->tenant->id}/api/settings/education-levels", $payload);

        // ASSERT
        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Master Degree');
        $response->assertJsonPath('data.index', 10);
        $response->assertJsonPath('message', __('Education level created successfully'));

        $this->assertDatabaseHas('education_levels', [
            'name' => 'Master Degree',
            'index' => 10,
        ]);
    }

    public function test_fails_to_create_if_name_is_missing(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $payload = ['index' => 5];

        // ACT
        $response = $this->postJson("/{$this->tenant->id}/api/settings/education-levels", $payload);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_fails_to_create_if_index_is_missing(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $payload = ['name' => 'Test'];

        // ACT
        $response = $this->postJson("/{$this->tenant->id}/api/settings/education-levels", $payload);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['index']);
    }

    public function test_fails_to_create_duplicate_name(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $uniqueName = 'Test Education ' . uniqid();
        EducationLevel::factory()->create(['name' => $uniqueName]);

        $payload = [
            'name' => $uniqueName,
            'index' => 10,
        ];

        // ACT
        $response = $this->postJson("/{$this->tenant->id}/api/settings/education-levels", $payload);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Test: Create restores soft-deleted education level dengan index update
     * 
     * Scenario: User delete education level, lalu create lagi dengan nama sama.
     * System harus restore record yang di-delete, bukan create duplicate.
     * 
     * Coverage Target: EducationLevelService line 21-27 (restore path)
     */
    public function test_create_restores_soft_deleted_with_index_update(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        
        // Step 1: Create education level
        $originalLevel = EducationLevel::factory()->create([
            'name' => 'Bachelor Degree',
            'index' => 10,
        ]);
        $originalId = $originalLevel->id;
        
        // Step 2: Soft delete it
        $originalLevel->delete();
        $this->assertSoftDeleted('education_levels', ['id' => $originalId]);
        
        // Step 3: Try to create with same name but different index
        $payload = [
            'name' => 'Bachelor Degree',
            'index' => 20, // New index - covers line 22-24
        ];
        
        // ACT
        $response = $this->postJson(
            "/{$this->tenant->id}/api/settings/education-levels",
            $payload
        );
        
        // ASSERT
        $response->assertStatus(201);
        $response->assertJsonPath('data.id', $originalId); // Same ID = restored
        $response->assertJsonPath('data.name', 'Bachelor Degree');
        $response->assertJsonPath('data.index', 20); // Updated index
        $response->assertJsonPath('message', __('Education level created successfully'));
        
        // Verify record was restored (not created new)
        $this->assertDatabaseHas('education_levels', [
            'id' => $originalId,
            'name' => 'Bachelor Degree',
            'index' => 20,
            'deleted_at' => null, // Restored
        ]);
        
        // Verify only 1 record exists (not 2)
        $count = EducationLevel::where('name', 'Bachelor Degree')->count();
        $this->assertEquals(1, $count, 'Should restore existing, not create new');
    }

    /**
     * Test: Create restores soft-deleted tanpa mengubah index
     * 
     * Scenario: Restore soft-deleted education level dengan index yang sama.
     * Test ini cover line 21, 27 tanpa execute line 22-24 (skip index update).
     * 
     * Coverage Target: EducationLevelService line 21, 27
     */
    public function test_create_restores_soft_deleted_preserving_index(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        
        $originalLevel = EducationLevel::factory()->create([
            'name' => 'Master Degree',
            'index' => 15,
        ]);
        $originalId = $originalLevel->id;
        $originalIndex = $originalLevel->index;
        
        $originalLevel->delete();
        $this->assertSoftDeleted('education_levels', ['id' => $originalId]);
        
        // ACT - Create with same name and same index (no update needed)
        $payload = [
            'name' => 'Master Degree',
            'index' => 15, // Same index
        ];
        
        $response = $this->postJson(
            "/{$this->tenant->id}/api/settings/education-levels",
            $payload
        );
        
        // ASSERT
        $response->assertStatus(201);
        $response->assertJsonPath('data.id', $originalId);
        $response->assertJsonPath('data.name', 'Master Degree');
        $response->assertJsonPath('data.index', $originalIndex); // Unchanged
        
        // Verify restored with original data
        $this->assertDatabaseHas('education_levels', [
            'id' => $originalId,
            'name' => 'Master Degree',
            'index' => $originalIndex,
            'deleted_at' => null,
        ]);
        
        // Verify no duplicate
        $count = EducationLevel::where('name', 'Master Degree')->count();
        $this->assertEquals(1, $count);
    }

    // ===================================================================================
    // TEST: SHOW EDUCATION LEVEL (GET /api/settings/education-levels/{id})
    // ===================================================================================

    public function test_can_show_education_level_detail(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $level = EducationLevel::factory()->create();

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/education-levels/{$level->id}");

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('data.id', $level->id);
        $response->assertJsonPath('data.name', $level->name);
        $response->assertJsonPath('data.index', $level->index);
    }

    public function test_fails_to_show_if_not_found(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/education-levels/99999");

        // ASSERT
        $response->assertNotFound();
    }

    // ===================================================================================
    // TEST: UPDATE EDUCATION LEVEL (PUT /api/settings/education-levels/{id})
    // ===================================================================================

    public function test_can_update_education_level(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $level = EducationLevel::factory()->create(['index' => 5]);

        $payload = [
            'name' => 'Updated Name',
            'index' => 5, // Tidak mengubah index, hanya name
        ];

        // ACT
        $response = $this->putJson("/{$this->tenant->id}/api/settings/education-levels/{$level->id}", $payload);

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('data.name', 'Updated Name');
        $response->assertJsonPath('message', __('Education level updated successfully'));

        $this->assertDatabaseHas('education_levels', [
            'id' => $level->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_update_partial_fields(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $level = EducationLevel::factory()->create();
        $originalIndex = $level->index;

        $payload = ['name' => 'Updated Name ' . uniqid()];

        // ACT
        $response = $this->putJson("/{$this->tenant->id}/api/settings/education-levels/{$level->id}", $payload);

        // ASSERT
        $response->assertOk();
        $this->assertStringContainsString('Updated Name', $response->json('data.name'));
        // Index should remain unchanged
        $this->assertDatabaseHas('education_levels', [
            'id' => $level->id,
            'index' => $originalIndex,
        ]);
    }

    public function test_can_reorder_education_level(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        
        // Buat 3 level dengan index berurutan
        $level1 = EducationLevel::factory()->create(['name' => 'Level 1', 'index' => 0]);
        $level2 = EducationLevel::factory()->create(['name' => 'Level 2', 'index' => 1]);
        $level3 = EducationLevel::factory()->create(['name' => 'Level 3', 'index' => 2]);

        // Ubah index level3 ke posisi 0 (pindah ke paling atas)
        $payload = [
            'name' => 'Level 3',
            'index' => 0,
        ];

        // ACT
        $response = $this->putJson("/{$this->tenant->id}/api/settings/education-levels/{$level3->id}", $payload);

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('data.name', 'Level 3');
        
        // Verify reindexing terjadi dengan benar
        $this->assertDatabaseHas('education_levels', [
            'id' => $level3->id,
            'index' => 0,
        ]);
    }

    public function test_fails_to_update_with_duplicate_name(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $name1 = 'Level ' . uniqid();
        $name2 = 'Level ' . uniqid();
        
        EducationLevel::factory()->create(['name' => $name1]);
        $level = EducationLevel::factory()->create(['name' => $name2]);

        $payload = ['name' => $name1];

        // ACT
        $response = $this->putJson("/{$this->tenant->id}/api/settings/education-levels/{$level->id}", $payload);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    // ===================================================================================
    // TEST: DELETE EDUCATION LEVEL (DELETE /api/settings/education-levels/{id})
    // ===================================================================================

    public function test_can_soft_delete_education_level(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $level = EducationLevel::factory()->create();

        // ACT
        $response = $this->deleteJson("/{$this->tenant->id}/api/settings/education-levels/{$level->id}");

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('message', __('Education level deleted successfully'));

        $this->assertSoftDeleted('education_levels', [
            'id' => $level->id,
        ]);
    }

    public function test_fails_to_delete_if_not_found(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        // ACT
        $response = $this->deleteJson("/{$this->tenant->id}/api/settings/education-levels/99999");

        // ASSERT
        $response->assertNotFound();
    }

    // ===================================================================================
    // TEST: ARCHIVED EDUCATION LEVELS (GET /api/settings/education-levels/archived)
    // ===================================================================================

    public function test_can_list_archived_education_levels(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        
        // Create active
        EducationLevel::factory()->count(2)->create();
        
        // Create and delete (archived)
        $archived1 = EducationLevel::factory()->create();
        $archived1->delete();
        
        $archived2 = EducationLevel::factory()->create();
        $archived2->delete();

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/education-levels/archived");

        // ASSERT
        $response->assertOk();
        $this->assertEquals(2, count($response->json('data')));
    }

    // ===================================================================================
    // TEST: RESTORE EDUCATION LEVEL (POST /api/settings/education-levels/{id}/restore)
    // ===================================================================================

    public function test_can_restore_deleted_education_level(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $level = EducationLevel::factory()->create();
        $level->delete();

        $this->assertSoftDeleted('education_levels', ['id' => $level->id]);

        // ACT
        $response = $this->postJson("/{$this->tenant->id}/api/settings/education-levels/{$level->id}/restore");

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('message', __('Education level restored successfully'));

        $this->assertDatabaseHas('education_levels', [
            'id' => $level->id,
            'deleted_at' => null,
        ]);
    }

    public function test_fails_to_restore_if_not_deleted(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $level = EducationLevel::factory()->create();

        // ACT
        $response = $this->postJson("/{$this->tenant->id}/api/settings/education-levels/{$level->id}/restore");

        // ASSERT
        $response->assertNotFound();
    }

    // ===================================================================================
    // TEST: FORCE DELETE (DELETE /api/settings/education-levels/{id}/force)
    // ===================================================================================

    public function test_can_force_delete_education_level(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $level = EducationLevel::factory()->create();
        $level->delete(); // Soft delete first
        $levelId = $level->id;

        // ACT
        $response = $this->deleteJson("/{$this->tenant->id}/api/settings/education-levels/{$levelId}/force");

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('message', __('Education level permanently deleted'));

        $this->assertDatabaseMissing('education_levels', [
            'id' => $levelId,
        ]);
    }

    public function test_fails_to_force_delete_if_not_soft_deleted(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $level = EducationLevel::factory()->create();

        // ACT
        $response = $this->deleteJson("/{$this->tenant->id}/api/settings/education-levels/{$level->id}/force");

        // ASSERT
        $response->assertNotFound();
    }
}
