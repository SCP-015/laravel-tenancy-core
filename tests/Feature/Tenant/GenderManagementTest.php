<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant\Gender;
use Tests\Feature\TenantTestCase;

/**
 * ======================================================================
 * Test untuk flow: Gender Management (Master Data - Read Only)
 * ======================================================================
 *
 * CATATAN PENTING:
 * Gender adalah data FIXED yang hanya bisa dibaca (READ ONLY).
 * - Data: 'm' (Male) dan 'f' (Female)
 * - Tidak ada endpoint create/update/delete
 * - Data ini di-seed saat tenant creation
 *
 * Target Coverage:
 * - GenderController: 0% → 80%+
 * - GenderService: 0% → 80%+
 *
 * Cara menjalankan test ini:
 * php artisan test tests/Feature/Tenant/GenderManagementTest.php
 * ======================================================================
 */
class GenderManagementTest extends TenantTestCase
{
    // ===================================================================================
    // TEST: LIST GENDERS (GET /api/settings/genders) - READ ONLY
    // ===================================================================================

    public function test_can_list_all_genders(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/genders");

        // ASSERT
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name'],
            ],
        ]);
    }

    public function test_returns_exactly_two_genders(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/genders");

        // ASSERT
        $response->assertOk();
        $data = $response->json('data');
        
        // Harus ada exactly 2 gender
        $this->assertCount(2, $data);
    }

    public function test_returns_male_and_female_genders(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/genders");

        // ASSERT
        $response->assertOk();
        $data = $response->json('data');
        
        // Extract IDs
        $ids = array_column($data, 'id');
        
        // Harus ada 'm' dan 'f'
        $this->assertContains('m', $ids);
        $this->assertContains('f', $ids);
    }

    public function test_gender_data_structure_is_correct(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/genders");

        // ASSERT
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
    }

    public function test_male_gender_has_correct_data(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/genders");

        // ASSERT
        $response->assertOk();
        $data = $response->json('data');
        
        // Find male gender
        $male = collect($data)->firstWhere('id', 'm');
        
        $this->assertNotNull($male);
        $this->assertEquals('m', $male['id']);
        $this->assertEquals('Male', $male['name']);
    }

    public function test_female_gender_has_correct_data(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/genders");

        // ASSERT
        $response->assertOk();
        $data = $response->json('data');
        
        // Find female gender
        $female = collect($data)->firstWhere('id', 'f');
        
        $this->assertNotNull($female);
        $this->assertEquals('f', $female['id']);
        $this->assertEquals('Female', $female['name']);
    }

    public function test_fails_to_list_if_unauthenticated(): void
    {
        // ACT
        $response = $this->getJson("/{$this->tenant->id}/api/settings/genders");

        // ASSERT
        $response->assertUnauthorized();
    }

    // ===================================================================================
    // TEST: VERIFY READ-ONLY BEHAVIOR
    // ===================================================================================

    public function test_cannot_create_new_gender_via_api(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $payload = [
            'id' => 'other',
            'name' => 'Other',
        ];

        // ACT - Try to POST (should not exist)
        $response = $this->postJson("/{$this->tenant->id}/api/settings/genders", $payload);

        // ASSERT - Should return 405 Method Not Allowed or 404 Not Found
        $this->assertContains($response->status(), [404, 405]);
    }

    public function test_cannot_update_gender_via_api(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();
        $payload = ['name' => 'Updated Male'];

        // ACT - Try to PUT (should not exist)
        $response = $this->putJson("/{$this->tenant->id}/api/settings/genders/m", $payload);

        // ASSERT - Should return 405 Method Not Allowed or 404 Not Found
        $this->assertContains($response->status(), [404, 405]);
    }

    public function test_cannot_delete_gender_via_api(): void
    {
        // ARRANGE
        $this->actingAsTenantOwner();

        // ACT - Try to DELETE (should not exist)
        $response = $this->deleteJson("/{$this->tenant->id}/api/settings/genders/m");

        // ASSERT - Should return 405 Method Not Allowed or 404 Not Found
        $this->assertContains($response->status(), [404, 405]);
    }

    // ===================================================================================
    // TEST: DATABASE INTEGRITY
    // ===================================================================================

    public function test_genders_exist_in_database(): void
    {
        // ASSERT
        $this->assertDatabaseHas('genders', ['id' => 'm', 'name' => 'Male']);
        $this->assertDatabaseHas('genders', ['id' => 'f', 'name' => 'Female']);
    }

    public function test_only_two_genders_in_database(): void
    {
        // ACT
        $count = Gender::count();

        // ASSERT
        $this->assertEquals(2, $count, 'Database should only contain exactly 2 genders (m and f)');
    }
}
