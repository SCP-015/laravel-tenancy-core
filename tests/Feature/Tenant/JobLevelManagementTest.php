<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant\JobLevel;
use Tests\Feature\TenantTestCase;

/**
 * ======================================================================
 * Test untuk flow: Job Level Management (Master Data)
 * ======================================================================
 *
 * Target Coverage:
 * - JobLevelController: 0% → 80%+
 * - JobLevelService: 0% → 80%+
 *
 * Cara menjalankan test ini:
 * php artisan test tests/Feature/Tenant/JobLevelManagementTest.php
 * ======================================================================
 */
class JobLevelManagementTest extends TenantTestCase
{
    // ===================================================================================
    // TEST: LIST JOB LEVELS
    // ===================================================================================

    public function test_can_list_all_job_levels(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        JobLevel::factory()->count(5)->create();

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/job-levels");

        // ASSERT
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'index'],
            ],
        ]);
        $this->assertGreaterThanOrEqual(5, count($response->json('data')));
    }

    public function test_job_levels_ordered_by_index(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        JobLevel::factory()->create(['name' => 'High Level', 'index' => 30]);
        JobLevel::factory()->create(['name' => 'Low Level', 'index' => 10]);
        JobLevel::factory()->create(['name' => 'Mid Level', 'index' => 20]);

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/job-levels");

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
        $response = $this->getJson("/{$this->tenant->id}/api/settings/job-levels");

        // ASSERT
        $response->assertUnauthorized();
    }

    // ===================================================================================
    // TEST: CREATE JOB LEVEL
    // ===================================================================================

    public function test_can_create_job_level(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $payload = [
            'name' => 'Director',
            'index' => 10,
        ];

        // ACT
        $response = $this->postJson("/{$this->tenant->id}/api/settings/job-levels", $payload);

        // ASSERT
        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Director');
        $response->assertJsonPath('data.index', 10);

        $this->assertDatabaseHas('job_levels', [
            'name' => 'Director',
            'index' => 10,
        ]);
    }

    public function test_fails_to_create_if_required_fields_missing(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $payload = [];

        // ACT
        $response = $this->postJson("/{$this->tenant->id}/api/settings/job-levels", $payload);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'index']);
    }

    public function test_fails_to_create_duplicate_name(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $uniqueName = 'Test Level ' . uniqid();
        JobLevel::factory()->create(['name' => $uniqueName]);

        $payload = [
            'name' => $uniqueName,
            'index' => 10,
        ];

        // ACT
        $response = $this->postJson("/{$this->tenant->id}/api/settings/job-levels", $payload);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Test: Create restores soft-deleted job level dengan index update
     * 
     * Scenario: User delete job level, lalu create lagi dengan nama sama.
     * System harus restore record yang di-delete, bukan create duplicate.
     * 
     * Coverage Target: JobLevelService line 23-29 (restore path)
     */
    public function test_create_restores_soft_deleted_with_index_update(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        
        // Step 1: Create job level
        $originalLevel = JobLevel::factory()->create([
            'name' => 'Junior Level',
            'index' => 10,
        ]);
        $originalId = $originalLevel->id;
        
        // Step 2: Soft delete it
        $originalLevel->delete();
        $this->assertSoftDeleted('job_levels', ['id' => $originalId]);
        
        // Step 3: Try to create with same name but different index
        $payload = [
            'name' => 'Junior Level',
            'index' => 20, // New index - covers line 24-26
        ];
        
        // ACT
        $response = $this->postJson(
            "/{$this->tenant->id}/api/settings/job-levels",
            $payload
        );
        
        // ASSERT
        $response->assertStatus(201);
        $response->assertJsonPath('data.id', $originalId); // Same ID = restored
        $response->assertJsonPath('data.name', 'Junior Level');
        $response->assertJsonPath('data.index', 20); // Updated index
        $response->assertJsonPath('message', __('Job level created successfully'));
        
        // Verify record was restored (not created new)
        $this->assertDatabaseHas('job_levels', [
            'id' => $originalId,
            'name' => 'Junior Level',
            'index' => 20,
            'deleted_at' => null, // Restored
        ]);
        
        // Verify only 1 record exists (not 2)
        $count = JobLevel::where('name', 'Junior Level')->count();
        $this->assertEquals(1, $count, 'Should restore existing, not create new');
    }

    /**
     * Test: Create restores soft-deleted tanpa mengubah index
     * 
     * Scenario: Restore soft-deleted job level dengan index yang sama.
     * Test ini cover line 23, 29 tanpa execute line 24-26 (skip index update).
     * 
     * Coverage Target: JobLevelService line 23, 29
     */
    public function test_create_restores_soft_deleted_preserving_index(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        
        $originalLevel = JobLevel::factory()->create([
            'name' => 'Senior Level',
            'index' => 15,
        ]);
        $originalId = $originalLevel->id;
        $originalIndex = $originalLevel->index;
        
        $originalLevel->delete();
        $this->assertSoftDeleted('job_levels', ['id' => $originalId]);
        
        // ACT - Create with same name and same index (no update needed)
        $payload = [
            'name' => 'Senior Level',
            'index' => 15, // Same index
        ];
        
        $response = $this->postJson(
            "/{$this->tenant->id}/api/settings/job-levels",
            $payload
        );
        
        // ASSERT
        $response->assertStatus(201);
        $response->assertJsonPath('data.id', $originalId);
        $response->assertJsonPath('data.name', 'Senior Level');
        $response->assertJsonPath('data.index', $originalIndex); // Unchanged
        
        // Verify restored with original data
        $this->assertDatabaseHas('job_levels', [
            'id' => $originalId,
            'name' => 'Senior Level',
            'index' => $originalIndex,
            'deleted_at' => null,
        ]);
        
        // Verify no duplicate
        $count = JobLevel::where('name', 'Senior Level')->count();
        $this->assertEquals(1, $count);
    }

    // ===================================================================================
    // TEST: SHOW JOB LEVEL
    // ===================================================================================

    public function test_can_show_job_level_detail(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $level = JobLevel::factory()->create();

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/job-levels/{$level->id}");

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
        $response = $this->getJson("/{$this->tenant->id}/api/settings/job-levels/99999");

        // ASSERT
        $response->assertNotFound();
    }

    // ===================================================================================
    // TEST: UPDATE JOB LEVEL
    // ===================================================================================

    public function test_can_update_job_level(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $level = JobLevel::factory()->create(['index' => 5]);

        $payload = [
            'name' => 'Updated Job Level',
            'index' => 5, // Tidak mengubah index, hanya name
        ];

        // ACT
        $response = $this->putJson("/{$this->tenant->id}/api/settings/job-levels/{$level->id}", $payload);

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('data.name', 'Updated Job Level');

        $this->assertDatabaseHas('job_levels', [
            'id' => $level->id,
            'name' => 'Updated Job Level',
        ]);
    }

    public function test_can_update_partial_fields(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $level = JobLevel::factory()->create();

        $payload = ['name' => 'Updated Name ' . uniqid()];

        // ACT
        $response = $this->putJson("/{$this->tenant->id}/api/settings/job-levels/{$level->id}", $payload);

        // ASSERT
        $response->assertOk();
        $this->assertStringContainsString('Updated Name', $response->json('data.name'));
    }

    public function test_can_reorder_job_level(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        
        // Buat 3 level dengan index berurutan
        $level1 = JobLevel::factory()->create(['name' => 'Level 1', 'index' => 0]);
        $level2 = JobLevel::factory()->create(['name' => 'Level 2', 'index' => 1]);
        $level3 = JobLevel::factory()->create(['name' => 'Level 3', 'index' => 2]);

        // Ubah index level3 ke posisi 0 (pindah ke paling atas)
        $payload = [
            'name' => 'Level 3',
            'index' => 0,
        ];

        // ACT
        $response = $this->putJson("/{$this->tenant->id}/api/settings/job-levels/{$level3->id}", $payload);

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('data.name', 'Level 3');
        
        // Verify reindexing terjadi dengan benar
        $this->assertDatabaseHas('job_levels', [
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
        
        JobLevel::factory()->create(['name' => $name1]);
        $level = JobLevel::factory()->create(['name' => $name2]);

        $payload = ['name' => $name1];

        // ACT
        $response = $this->putJson("/{$this->tenant->id}/api/settings/job-levels/{$level->id}", $payload);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    // ===================================================================================
    // TEST: DELETE JOB LEVEL
    // ===================================================================================

    public function test_can_soft_delete_job_level(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $level = JobLevel::factory()->create();

        // ACT
        $response = $this->deleteJson("/{$this->tenant->id}/api/settings/job-levels/{$level->id}");

        // ASSERT
        $response->assertOk();
        $this->assertSoftDeleted('job_levels', ['id' => $level->id]);
    }

    public function test_fails_to_delete_if_not_found(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        // ACT
        $response = $this->deleteJson("/{$this->tenant->id}/api/settings/job-levels/99999");

        // ASSERT
        $response->assertNotFound();
    }

    // ===================================================================================
    // TEST: ARCHIVED & RESTORE
    // ===================================================================================

    public function test_can_list_archived_job_levels(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        
        JobLevel::factory()->count(3)->create();
        
        $archived1 = JobLevel::factory()->create();
        $archived1->delete();
        
        $archived2 = JobLevel::factory()->create();
        $archived2->delete();

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/job-levels/archived");

        // ASSERT
        $response->assertOk();
        $this->assertEquals(2, count($response->json('data')));
    }

    public function test_can_restore_deleted_job_level(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $level = JobLevel::factory()->create();
        $level->delete();

        // ACT
        $response = $this->postJson("/{$this->tenant->id}/api/settings/job-levels/{$level->id}/restore");

        // ASSERT
        $response->assertOk();
        $this->assertDatabaseHas('job_levels', [
            'id' => $level->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_force_delete_job_level(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $level = JobLevel::factory()->create();
        $level->delete();
        $levelId = $level->id;

        // ACT
        $response = $this->deleteJson("/{$this->tenant->id}/api/settings/job-levels/{$levelId}/force");

        // ASSERT
        $response->assertOk();
        $this->assertDatabaseMissing('job_levels', ['id' => $levelId]);
    }
}
