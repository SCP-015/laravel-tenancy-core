<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant\ExperienceLevel;
use Tests\Feature\TenantTestCase;

/**
 * ======================================================================
 * Test untuk flow: Experience Level Management (Master Data)
 * ======================================================================
 *
 * Target Coverage:
 * - ExperienceLevelController: 0% → 80%+
 * - ExperienceLevelService: 0% → 80%+
 *
 * Cara menjalankan test ini:
 * php artisan test tests/Feature/Tenant/ExperienceLevelManagementTest.php
 * ======================================================================
 */
class ExperienceLevelManagementTest extends TenantTestCase
{
    // ===================================================================================
    // TEST: LIST EXPERIENCE LEVELS
    // ===================================================================================

    public function test_can_list_all_experience_levels(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        ExperienceLevel::factory()->count(4)->create();

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/experience-levels");

        // ASSERT
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'index'],
            ],
        ]);
        $this->assertGreaterThanOrEqual(4, count($response->json('data')));
    }

    public function test_experience_levels_ordered_by_index(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        ExperienceLevel::factory()->create(['name' => 'High Exp', 'index' => 30]);
        ExperienceLevel::factory()->create(['name' => 'Low Exp', 'index' => 10]);
        ExperienceLevel::factory()->create(['name' => 'Mid Exp', 'index' => 20]);

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/experience-levels");

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
        $response = $this->getJson("/{$this->tenant->id}/api/settings/experience-levels");

        // ASSERT
        $response->assertUnauthorized();
    }

    // ===================================================================================
    // TEST: CREATE EXPERIENCE LEVEL
    // ===================================================================================

    public function test_can_create_experience_level(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $payload = [
            'name' => '10+ Years',
            'index' => 6,
        ];

        // ACT
        $response = $this->postJson("/{$this->tenant->id}/api/settings/experience-levels", $payload);

        // ASSERT
        $response->assertStatus(201);
        $response->assertJsonPath('data.name', '10+ Years');
        $response->assertJsonPath('data.index', 6);

        $this->assertDatabaseHas('experience_levels', [
            'name' => '10+ Years',
            'index' => 6,
        ]);
    }

    public function test_fails_to_create_if_required_fields_missing(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $payload = [];

        // ACT
        $response = $this->postJson("/{$this->tenant->id}/api/settings/experience-levels", $payload);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'index']);
    }

    public function test_fails_to_create_duplicate_name(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $uniqueName = 'Test Experience ' . uniqid();
        ExperienceLevel::factory()->create(['name' => $uniqueName]);

        $payload = [
            'name' => $uniqueName,
            'index' => 10,
        ];

        // ACT
        $response = $this->postJson("/{$this->tenant->id}/api/settings/experience-levels", $payload);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Test: Create restores soft-deleted experience level dengan index update
     * 
     * Scenario: User delete experience level, lalu create lagi dengan nama sama.
     * System harus restore record yang di-delete, bukan create duplicate.
     * 
     * Coverage Target: ExperienceLevelService line 21-27 (restore path)
     */
    public function test_create_restores_soft_deleted_with_index_update(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        
        // Step 1: Create experience level
        $originalLevel = ExperienceLevel::factory()->create([
            'name' => 'Entry Level',
            'index' => 10,
        ]);
        $originalId = $originalLevel->id;
        
        // Step 2: Soft delete it
        $originalLevel->delete();
        $this->assertSoftDeleted('experience_levels', ['id' => $originalId]);
        
        // Step 3: Try to create with same name but different index
        $payload = [
            'name' => 'Entry Level',
            'index' => 20, // New index - covers line 22-24
        ];
        
        // ACT
        $response = $this->postJson(
            "/{$this->tenant->id}/api/settings/experience-levels",
            $payload
        );
        
        // ASSERT
        $response->assertStatus(201);
        $response->assertJsonPath('data.id', $originalId); // Same ID = restored
        $response->assertJsonPath('data.name', 'Entry Level');
        $response->assertJsonPath('data.index', 20); // Updated index
        $response->assertJsonPath('message', __('Experience level created successfully'));
        
        // Verify record was restored (not created new)
        $this->assertDatabaseHas('experience_levels', [
            'id' => $originalId,
            'name' => 'Entry Level',
            'index' => 20,
            'deleted_at' => null, // Restored
        ]);
        
        // Verify only 1 record exists (not 2)
        $count = ExperienceLevel::where('name', 'Entry Level')->count();
        $this->assertEquals(1, $count, 'Should restore existing, not create new');
    }

    /**
     * Test: Create restores soft-deleted tanpa mengubah index
     * 
     * Scenario: Restore soft-deleted experience level dengan index yang sama.
     * Test ini cover line 21, 27 tanpa execute line 22-24 (skip index update).
     * 
     * Coverage Target: ExperienceLevelService line 21, 27
     */
    public function test_create_restores_soft_deleted_preserving_index(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        
        $originalLevel = ExperienceLevel::factory()->create([
            'name' => 'Senior Level',
            'index' => 15,
        ]);
        $originalId = $originalLevel->id;
        $originalIndex = $originalLevel->index;
        
        $originalLevel->delete();
        $this->assertSoftDeleted('experience_levels', ['id' => $originalId]);
        
        // ACT - Create with same name and same index (no update needed)
        $payload = [
            'name' => 'Senior Level',
            'index' => 15, // Same index
        ];
        
        $response = $this->postJson(
            "/{$this->tenant->id}/api/settings/experience-levels",
            $payload
        );
        
        // ASSERT
        $response->assertStatus(201);
        $response->assertJsonPath('data.id', $originalId);
        $response->assertJsonPath('data.name', 'Senior Level');
        $response->assertJsonPath('data.index', $originalIndex); // Unchanged
        
        // Verify restored with original data
        $this->assertDatabaseHas('experience_levels', [
            'id' => $originalId,
            'name' => 'Senior Level',
            'index' => $originalIndex,
            'deleted_at' => null,
        ]);
        
        // Verify no duplicate
        $count = ExperienceLevel::where('name', 'Senior Level')->count();
        $this->assertEquals(1, $count);
    }

    // ===================================================================================
    // TEST: SHOW EXPERIENCE LEVEL
    // ===================================================================================

    public function test_can_show_experience_level_detail(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $level = ExperienceLevel::factory()->create();

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/experience-levels/{$level->id}");

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('data.id', $level->id);
        $response->assertJsonPath('data.name', $level->name);
    }

    public function test_fails_to_show_if_not_found(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/experience-levels/99999");

        // ASSERT
        $response->assertNotFound();
    }

    // ===================================================================================
    // TEST: UPDATE EXPERIENCE LEVEL
    // ===================================================================================

    public function test_can_update_experience_level(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $level = ExperienceLevel::factory()->create(['index' => 5]);

        $payload = [
            'name' => 'Updated Experience',
            'index' => 5, // Tidak mengubah index, hanya name
        ];

        // ACT
        $response = $this->putJson("/{$this->tenant->id}/api/settings/experience-levels/{$level->id}", $payload);

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('data.name', 'Updated Experience');

        $this->assertDatabaseHas('experience_levels', [
            'id' => $level->id,
            'name' => 'Updated Experience',
        ]);
    }

    public function test_can_reorder_experience_level(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        
        // Buat 3 level dengan index berurutan
        $level1 = ExperienceLevel::factory()->create(['name' => 'Level 1', 'index' => 0]);
        $level2 = ExperienceLevel::factory()->create(['name' => 'Level 2', 'index' => 1]);
        $level3 = ExperienceLevel::factory()->create(['name' => 'Level 3', 'index' => 2]);

        // Ubah index level3 ke posisi 0 (pindah ke paling atas)
        $payload = [
            'name' => 'Level 3',
            'index' => 0,
        ];

        // ACT
        $response = $this->putJson("/{$this->tenant->id}/api/settings/experience-levels/{$level3->id}", $payload);

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('data.name', 'Level 3');
        
        // Verify reindexing terjadi dengan benar
        $this->assertDatabaseHas('experience_levels', [
            'id' => $level3->id,
            'index' => 0,
        ]);
    }

    public function test_fails_to_update_with_duplicate_name(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $name1 = 'Exp ' . uniqid();
        $name2 = 'Exp ' . uniqid();
        
        ExperienceLevel::factory()->create(['name' => $name1]);
        $level = ExperienceLevel::factory()->create(['name' => $name2]);

        $payload = ['name' => $name1];

        // ACT
        $response = $this->putJson("/{$this->tenant->id}/api/settings/experience-levels/{$level->id}", $payload);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    // ===================================================================================
    // TEST: DELETE EXPERIENCE LEVEL
    // ===================================================================================

    public function test_can_soft_delete_experience_level(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $level = ExperienceLevel::factory()->create();

        // ACT
        $response = $this->deleteJson("/{$this->tenant->id}/api/settings/experience-levels/{$level->id}");

        // ASSERT
        $response->assertOk();
        $this->assertSoftDeleted('experience_levels', ['id' => $level->id]);
    }

    // ===================================================================================
    // TEST: ARCHIVED & RESTORE
    // ===================================================================================

    public function test_can_list_archived_experience_levels(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        
        ExperienceLevel::factory()->count(2)->create();
        
        $archived = ExperienceLevel::factory()->create();
        $archived->delete();

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/experience-levels/archived");

        // ASSERT
        $response->assertOk();
        $this->assertEquals(1, count($response->json('data')));
    }

    public function test_can_restore_deleted_experience_level(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $level = ExperienceLevel::factory()->create();
        $level->delete();

        // ACT
        $response = $this->postJson("/{$this->tenant->id}/api/settings/experience-levels/{$level->id}/restore");

        // ASSERT
        $response->assertOk();
        $this->assertDatabaseHas('experience_levels', [
            'id' => $level->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_force_delete_experience_level(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $level = ExperienceLevel::factory()->create();
        $level->delete();
        $levelId = $level->id;

        // ACT
        $response = $this->deleteJson("/{$this->tenant->id}/api/settings/experience-levels/{$levelId}/force");

        // ASSERT
        $response->assertOk();
        $this->assertDatabaseMissing('experience_levels', ['id' => $levelId]);
    }
}
