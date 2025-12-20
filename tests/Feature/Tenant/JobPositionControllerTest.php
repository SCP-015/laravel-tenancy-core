<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant\JobPosition;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\TenantTestCase;

/**
 * Test suite untuk JobPositionController
 * Target: 100% coverage
 */
class JobPositionControllerTest extends TenantTestCase
{
    use WithFaker;

    /**
     * Test: Index returns job positions list successfully
     */
    public function test_index_returns_job_positions_list_successfully(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        
        $this->tenant->run(function () {
            JobPosition::factory()->count(3)->create();
        });

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/job-positions");

        // ASSERT
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'id_parent',
                ],
            ],
            'message',
        ]);
    }

    /**
     * Test: Index with pagination returns paginated structure
     */
    public function test_index_with_pagination_returns_paginated_structure(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        $this->tenant->run(function () {
            JobPosition::factory()->count(5)->create();
        });

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/job-positions?per_page=2&page=1");

        // ASSERT
        $response->assertOk();
        $response->assertJsonStructure([
            'code',
            'title',
            'message',
            'data' => [
                'items' => [
                    '*' => [
                        'id',
                        'name',
                        'id_parent',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ],
        ]);

        $meta = $response->json('data.meta');

        $this->assertEquals(1, $meta['current_page']);
        $this->assertEquals(2, $meta['per_page']);
        $this->assertGreaterThanOrEqual(5, $meta['total']);
    }

    /**
     * Test: Index with pagination and search filters job positions
     * Purpose: Cover search branch in JobPositionService::paginate()
     */
    public function test_index_with_pagination_and_search_filters_results(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        $this->tenant->run(function () {
            JobPosition::factory()->create(['name' => 'Backend Engineer']);
            JobPosition::factory()->create(['name' => 'Senior Backend Engineer']);
            JobPosition::factory()->create(['name' => 'Frontend Engineer']);
        });

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/job-positions?per_page=10&page=1&search=Backend");

        // ASSERT
        $response->assertOk();
        $response->assertJsonStructure([
            'code',
            'title',
            'message',
            'data' => [
                'items' => [
                    '*' => [
                        'id',
                        'name',
                        'id_parent',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ],
        ]);

        // Minimal dua posisi dengan kata "Backend" harus muncul dan semua mengandung kata tersebut
        $items = $response->json('data.items');
        $this->assertGreaterThanOrEqual(2, count($items));

        foreach ($items as $item) {
            $this->assertStringContainsString('Backend', $item['name']);
        }
    }

    /**
     * Test: Index with pagination and source=manual filters job positions without nusawork_id
     * Purpose: Cover source=manual branch in JobPositionService::paginate()
     */
    public function test_index_with_pagination_and_source_manual_filters_results(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        $this->tenant->run(function () {
            // Posisi manual (belum terhubung ke Nusawork)
            JobPosition::factory()->create([
                'name' => 'Manual Position A',
                'nusawork_id' => null,
            ]);
            JobPosition::factory()->create([
                'name' => 'Manual Position B',
                'nusawork_id' => null,
            ]);

            // Posisi yang sudah terhubung ke Nusawork (harus ter-filter)
            JobPosition::factory()->create([
                'name' => 'Synced Position',
                'nusawork_id' => 1001,
                'nusawork_name' => 'Nusawork Position 1',
            ]);
        });

        // ACT - panggil API dengan filter source=manual
        $response = $this->getJson("/{$this->tenant->id}/api/settings/job-positions?per_page=10&page=1&source=manual");

        // ASSERT
        $response->assertOk();
        $response->assertJsonStructure([
            'code',
            'title',
            'message',
            'data' => [
                'items' => [
                    '*' => [
                        'id',
                        'name',
                        'id_parent',
                        'nusawork_id',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ],
        ]);

        $items = $response->json('data.items');
        $this->assertNotEmpty($items);

        // Semua item yang dikembalikan harus belum terhubung ke Nusawork (nusawork_id null)
        foreach ($items as $item) {
            $this->assertNull($item['nusawork_id']);
        }
    }

    /**
     * Test: Index with pagination and source=nusawork filters job positions with nusawork_id
     * Purpose: Cover source=nusawork branch in JobPositionService::paginate()
     */
    public function test_index_with_pagination_and_source_nusawork_filters_results(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        $this->tenant->run(function () {
            // Posisi manual (harus ter-filter)
            JobPosition::factory()->create([
                'name' => 'Manual Position A',
                'nusawork_id' => null,
            ]);

            // Posisi yang sudah terhubung ke Nusawork
            JobPosition::factory()->create([
                'name' => 'Synced Position 1',
                'nusawork_id' => 2001,
                'nusawork_name' => 'Nusawork Position 1',
            ]);
            JobPosition::factory()->create([
                'name' => 'Synced Position 2',
                'nusawork_id' => 2002,
                'nusawork_name' => 'Nusawork Position 2',
            ]);
        });

        // ACT - panggil API dengan filter source=nusawork
        $response = $this->getJson("/{$this->tenant->id}/api/settings/job-positions?per_page=10&page=1&source=nusawork");

        // ASSERT
        $response->assertOk();
        $response->assertJsonStructure([
            'code',
            'title',
            'message',
            'data' => [
                'items' => [
                    '*' => [
                        'id',
                        'name',
                        'id_parent',
                        'nusawork_id',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ],
        ]);

        $items = $response->json('data.items');
        $this->assertNotEmpty($items);

        // Semua item yang dikembalikan harus sudah terhubung ke Nusawork (nusawork_id tidak null)
        foreach ($items as $item) {
            $this->assertNotNull($item['nusawork_id']);
        }
    }

    /**
     * Test: Index fails without authentication
     */
    public function test_index_fails_without_authentication(): void
    {
        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/job-positions");

        // ASSERT
        $response->assertUnauthorized();
    }

    /**
     * Test: Store creates job position successfully
     */
    public function test_store_creates_job_position_successfully(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        $payload = [
            'name' => 'Backend Developer',
            'id_parent' => null,
        ];

        // ACT
        $response = $this->postJson("/{$this->tenant->id}/api/settings/job-positions", $payload);

        // ASSERT
        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Backend Developer');
        $response->assertJsonPath('message', __('Job position created successfully'));

        $this->tenant->run(function () {
            $this->assertDatabaseHas('job_positions', [
                'name' => 'Backend Developer',
            ]);
        });
    }

    /**
     * Test: Store creates job position with parent
     */
    public function test_store_creates_job_position_with_parent(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        $parentPosition = null;
        $this->tenant->run(function () use (&$parentPosition) {
            $parentPosition = JobPosition::factory()->create(['name' => 'Engineering']);
        });

        $payload = [
            'name' => 'Senior Backend Developer',
            'id_parent' => $parentPosition->id,
        ];

        // ACT
        $response = $this->postJson("/{$this->tenant->id}/api/settings/job-positions", $payload);

        // ASSERT
        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Senior Backend Developer');
        $response->assertJsonPath('data.id_parent', $parentPosition->id);
    }

    /**
     * Test: Store validates required name field
     */
    public function test_store_validates_required_name_field(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        // ACT
        $response = $this->postJson("/{$this->tenant->id}/api/settings/job-positions", [
            'name' => '',
        ]);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Test: Store fails with duplicate name
     */
    public function test_store_fails_with_duplicate_name(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        $this->tenant->run(function () {
            JobPosition::factory()->create(['name' => 'Frontend Developer']);
        });

        // ACT
        $response = $this->postJson("/{$this->tenant->id}/api/settings/job-positions", [
            'name' => 'Frontend Developer',
        ]);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Test: Store fails with invalid parent
     */
    public function test_store_fails_with_invalid_parent(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        // ACT
        $response = $this->postJson("/{$this->tenant->id}/api/settings/job-positions", [
            'name' => 'Backend Developer',
            'id_parent' => 99999,
        ]);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['id_parent']);
    }

    /**
     * Test: Store restores soft-deleted job position with same name
     */
    public function test_store_restores_soft_deleted_job_position(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        $deletedPosition = null;
        $this->tenant->run(function () use (&$deletedPosition) {
            $deletedPosition = JobPosition::factory()->create(['name' => 'Deleted Position']);
            $deletedPosition->delete(); // Soft delete
        });

        // ACT - try to create with same name
        $response = $this->postJson("/{$this->tenant->id}/api/settings/job-positions", [
            'name' => 'Deleted Position',
        ]);

        // ASSERT - should restore the deleted one
        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Deleted Position');
        
        $this->tenant->run(function () use ($deletedPosition) {
            $restored = JobPosition::find($deletedPosition->id);
            $this->assertNotNull($restored);
            $this->assertNull($restored->deleted_at);
        });
    }

    /**
     * Test: Show returns job position details
     */
    public function test_show_returns_job_position_details(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        $jobPosition = null;
        $this->tenant->run(function () use (&$jobPosition) {
            $jobPosition = JobPosition::factory()->create();
        });

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/job-positions/{$jobPosition->id}");

        // ASSERT
        $response->assertOk();
        $response->assertJsonStructure([
            'code',
            'title',
            'message',
            'data' => [
                'id',
                'name',
            ]
        ]);
        $response->assertJsonPath('data.id', $jobPosition->id);
        $response->assertJsonPath('data.name', $jobPosition->name);
    }

    /**
     * Test: Show returns 404 for non-existent job position
     */
    public function test_show_returns_404_for_nonexistent_job_position(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/job-positions/99999");

        // ASSERT
        $response->assertStatus(404);
    }

    /**
     * Test: Update updates job position successfully
     */
    public function test_update_updates_job_position_successfully(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        $jobPosition = null;
        $this->tenant->run(function () use (&$jobPosition) {
            $jobPosition = JobPosition::factory()->create(['name' => 'Old Name']);
        });

        // ACT
        $response = $this->putJson("/{$this->tenant->id}/api/settings/job-positions/{$jobPosition->id}", [
            'name' => 'New Name',
        ]);

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('data.name', 'New Name');

        $this->tenant->run(function () use ($jobPosition) {
            $this->assertDatabaseHas('job_positions', [
                'id' => $jobPosition->id,
                'name' => 'New Name',
            ]);
        });
    }

    /**
     * Test: Update validates name field
     */
    public function test_update_validates_name_field(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        $jobPosition = null;
        $this->tenant->run(function () use (&$jobPosition) {
            $jobPosition = JobPosition::factory()->create();
        });

        // ACT - send invalid name (too long)
        $response = $this->putJson("/{$this->tenant->id}/api/settings/job-positions/{$jobPosition->id}", [
            'name' => str_repeat('a', 256), // Exceeds 255 chars
        ]);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Test: Update can change parent
     */
    public function test_update_can_change_parent(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        $jobPosition = null;
        $newParent = null;
        $this->tenant->run(function () use (&$jobPosition, &$newParent) {
            $jobPosition = JobPosition::factory()->create(['id_parent' => null]);
            $newParent = JobPosition::factory()->create();
        });

        // ACT
        $response = $this->putJson("/{$this->tenant->id}/api/settings/job-positions/{$jobPosition->id}", [
            'id_parent' => $newParent->id,
        ]);

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('data.id_parent', $newParent->id);
    }

    /**
     * Test: Destroy soft deletes job position
     */
    public function test_destroy_soft_deletes_job_position(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        $jobPosition = null;
        $this->tenant->run(function () use (&$jobPosition) {
            $jobPosition = JobPosition::factory()->create();
        });

        // ACT
        $response = $this->deleteJson("/{$this->tenant->id}/api/settings/job-positions/{$jobPosition->id}");

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('message', __('Job position deleted successfully'));

        $this->tenant->run(function () use ($jobPosition) {
            $deleted = JobPosition::withTrashed()->find($jobPosition->id);
            $this->assertNotNull($deleted);
            $this->assertNotNull($deleted->deleted_at);
        });
    }

    /**
     * Test: Archived returns soft-deleted job positions
     */
    public function test_archived_returns_soft_deleted_job_positions(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        $this->tenant->run(function () {
            // Create and soft delete positions
            $position1 = JobPosition::factory()->create(['name' => 'Deleted 1']);
            $position2 = JobPosition::factory()->create(['name' => 'Deleted 2']);
            $position1->delete();
            $position2->delete();
            
            // Create active position (should not appear)
            JobPosition::factory()->create(['name' => 'Active']);
        });

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/job-positions/archived");

        // ASSERT
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    /**
     * Test: Restore restores soft-deleted job position
     */
    public function test_restore_restores_soft_deleted_job_position(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        $jobPosition = null;
        $this->tenant->run(function () use (&$jobPosition) {
            $jobPosition = JobPosition::factory()->create();
            $jobPosition->delete();
        });

        // ACT
        $response = $this->postJson("/{$this->tenant->id}/api/settings/job-positions/{$jobPosition->id}/restore");

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('message', __('Job position restored successfully'));

        $this->tenant->run(function () use ($jobPosition) {
            $restored = JobPosition::find($jobPosition->id);
            $this->assertNotNull($restored);
            $this->assertNull($restored->deleted_at);
        });
    }

    /**
     * Test: Restore returns 404 for non-deleted job position
     */
    public function test_restore_returns_404_for_non_deleted_job_position(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        // ACT - try to restore non-existent or non-deleted position
        $response = $this->postJson("/{$this->tenant->id}/api/settings/job-positions/99999/restore");

        // ASSERT
        $response->assertStatus(404);
    }

    /**
     * Test: ForceDelete permanently deletes job position
     */
    public function test_force_delete_permanently_deletes_job_position(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        $jobPosition = null;
        $this->tenant->run(function () use (&$jobPosition) {
            $jobPosition = JobPosition::factory()->create();
            $jobPosition->delete(); // Soft delete first
        });

        // ACT
        $response = $this->deleteJson("/{$this->tenant->id}/api/settings/job-positions/{$jobPosition->id}/force");

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('message', __('Job position permanently deleted'));

        $this->tenant->run(function () use ($jobPosition) {
            $deleted = JobPosition::withTrashed()->find($jobPosition->id);
            $this->assertNull($deleted); // Should not exist at all
        });
    }

    /**
     * Test: ForceDelete returns 404 for non-deleted job position
     */
    public function test_force_delete_returns_404_for_non_deleted_job_position(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        // ACT
        $response = $this->deleteJson("/{$this->tenant->id}/api/settings/job-positions/99999/force");

        // ASSERT
        $response->assertStatus(404);
    }

    /**
     * Test: Store restores soft-deleted job position with new parent
     * Coverage: Line 25-26 di JobPositionService::create()
     */
    public function test_store_restores_soft_deleted_job_position_with_new_parent(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        $softDeletedPosition = null;
        $newParent = null;
        $this->tenant->run(function () use (&$softDeletedPosition, &$newParent) {
            // Buat job position dan soft delete
            $softDeletedPosition = JobPosition::factory()->create([
                'name' => 'Backend Developer',
                'id_parent' => null,
            ]);
            $softDeletedPosition->delete(); // Soft delete

            // Buat parent baru
            $newParent = JobPosition::factory()->create([
                'name' => 'Engineering Department',
            ]);
        });

        // ACT - Create dengan nama yang sama dan parent baru
        $response = $this->postJson("/{$this->tenant->id}/api/settings/job-positions", [
            'name' => 'Backend Developer',
            'id_parent' => $newParent->id,
        ]);

        // ASSERT
        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Backend Developer');
        $response->assertJsonPath('data.id_parent', $newParent->id);

        // Verify record di-restore dan parent di-update
        $this->tenant->run(function () use ($softDeletedPosition, $newParent) {
            $restored = JobPosition::find($softDeletedPosition->id);
            $this->assertNotNull($restored);
            $this->assertEquals($newParent->id, $restored->id_parent);
            $this->assertNull($restored->deleted_at);
        });
    }
}
