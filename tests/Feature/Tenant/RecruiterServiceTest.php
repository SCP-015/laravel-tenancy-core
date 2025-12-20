<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant;
use App\Models\User;
use App\Models\TenantUser;
use App\Services\Tenant\RecruiterService;
use Tests\Feature\TenantTestCase;

/**
 * Test untuk RecruiterService
 * 
 * Coverage target:
 * - Line 22-26: Search filter logic (case-insensitive search by name and email)
 * - Line 12-14: Filter parameters (per_page, page, search)
 * - Line 16-28: Query building and pagination
 */
class RecruiterServiceTest extends TenantTestCase
{
    protected RecruiterService $service;
    protected Tenant $tenant;
    protected User $recruiter1;
    protected User $recruiter2;
    protected User $recruiter3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RecruiterService::class);

        // Create test users
        $this->recruiter1 = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);

        $this->recruiter2 = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
        ]);

        $this->recruiter3 = User::factory()->create([
            'name' => 'Bob Johnson',
            'email' => 'bob.johnson@example.com',
        ]);

        // Get current tenant from context
        $this->tenant = tenant();

        // Attach users to tenant using sync to avoid unique constraint errors
        $this->tenant->users()->sync([
            $this->recruiter1->global_id => ['role' => 'admin'],
            $this->recruiter2->global_id => ['role' => 'admin'],
            $this->recruiter3->global_id => ['role' => 'admin'],
        ]);
    }

    /**
     * Test: getRecruiters returns paginated list without search
     * Coverage: Line 12-14, 16-28
     */
    public function test_get_recruiters_returns_paginated_list_without_search(): void
    {
        $this->tenant->run(function () {
            // ACT
            $result = $this->service->getRecruiters($this->tenant, [
                'per_page' => 10,
                'page' => 1,
                'search' => '',
            ]);

            // ASSERT
            $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
            $this->assertGreaterThanOrEqual(3, $result->total());
            $this->assertEquals(1, $result->currentPage());
            $this->assertEquals(10, $result->perPage());
        });
    }

    /**
     * Test: getRecruiters uses default per_page when not provided
     * Coverage: Line 12
     */
    public function test_get_recruiters_uses_default_per_page(): void
    {
        $this->tenant->run(function () {
            // ACT - Don't provide per_page
            $result = $this->service->getRecruiters($this->tenant, [
                'page' => 1,
                'search' => '',
            ]);

            // ASSERT - Should use default per_page of 10
            $this->assertEquals(10, $result->perPage());
        });
    }

    /**
     * Test: getRecruiters uses default page when not provided
     * Coverage: Line 13
     */
    public function test_get_recruiters_uses_default_page(): void
    {
        $this->tenant->run(function () {
            // ACT - Don't provide page
            $result = $this->service->getRecruiters($this->tenant, [
                'per_page' => 10,
                'search' => '',
            ]);

            // ASSERT - Should use default page of 1
            $this->assertEquals(1, $result->currentPage());
        });
    }

    /**
     * Test: getRecruiters uses custom per_page
     * Coverage: Line 12
     */
    public function test_get_recruiters_uses_custom_per_page(): void
    {
        $this->tenant->run(function () {
            // ACT
            $result = $this->service->getRecruiters($this->tenant, [
                'per_page' => 5,
                'page' => 1,
                'search' => '',
            ]);

            // ASSERT
            $this->assertEquals(5, $result->perPage());
        });
    }

    /**
     * Test: getRecruiters uses custom page
     * Coverage: Line 13
     */
    public function test_get_recruiters_uses_custom_page(): void
    {
        $this->tenant->run(function () {
            // ACT
            $result = $this->service->getRecruiters($this->tenant, [
                'per_page' => 10,
                'page' => 2,
                'search' => '',
            ]);

            // ASSERT
            $this->assertEquals(2, $result->currentPage());
        });
    }

    /**
     * Test: getRecruiters searches by name (case-insensitive)
     * Coverage: Line 22-26 (search by name)
     */
    public function test_get_recruiters_searches_by_name_case_insensitive(): void
    {
        $this->tenant->run(function () {
            // ACT - Search with different case
            $result = $this->service->getRecruiters($this->tenant, [
                'per_page' => 10,
                'page' => 1,
                'search' => 'JOHN',
            ]);

            // ASSERT
            $this->assertGreaterThan(0, $result->total());
            $names = $result->pluck('name')->toArray();
            $this->assertContains('John Doe', $names);
        });
    }

    /**
     * Test: getRecruiters searches by email (case-insensitive)
     * Coverage: Line 22-26 (search by email)
     */
    public function test_get_recruiters_searches_by_email_case_insensitive(): void
    {
        $this->tenant->run(function () {
            // ACT - Search with different case
            $result = $this->service->getRecruiters($this->tenant, [
                'per_page' => 10,
                'page' => 1,
                'search' => 'JANE.SMITH@EXAMPLE.COM',
            ]);

            // ASSERT
            $this->assertGreaterThan(0, $result->total());
            $emails = $result->pluck('email')->toArray();
            $this->assertContains('jane.smith@example.com', $emails);
        });
    }

    /**
     * Test: getRecruiters searches by partial name
     * Coverage: Line 24 (LIKE with partial match)
     */
    public function test_get_recruiters_searches_by_partial_name(): void
    {
        $this->tenant->run(function () {
            // ACT - Search with partial name
            $result = $this->service->getRecruiters($this->tenant, [
                'per_page' => 10,
                'page' => 1,
                'search' => 'john',
            ]);

            // ASSERT
            $this->assertGreaterThan(0, $result->total());
            $names = $result->pluck('name')->toArray();
            $this->assertContains('John Doe', $names);
            $this->assertContains('Bob Johnson', $names); // Contains 'john'
        });
    }

    /**
     * Test: getRecruiters searches by partial email
     * Coverage: Line 25 (LIKE with partial match)
     */
    public function test_get_recruiters_searches_by_partial_email(): void
    {
        $this->tenant->run(function () {
            // ACT - Search with partial email
            $result = $this->service->getRecruiters($this->tenant, [
                'per_page' => 10,
                'page' => 1,
                'search' => 'example.com',
            ]);

            // ASSERT
            $this->assertGreaterThan(0, $result->total());
            // All recruiters have example.com email
            $this->assertGreaterThanOrEqual(3, $result->total());
        });
    }

    /**
     * Test: getRecruiters returns empty result for non-matching search
     * Coverage: Line 22-26 (search with no results)
     */
    public function test_get_recruiters_returns_empty_for_non_matching_search(): void
    {
        $this->tenant->run(function () {
            // ACT - Search with non-matching term
            $result = $this->service->getRecruiters($this->tenant, [
                'per_page' => 10,
                'page' => 1,
                'search' => 'nonexistent_recruiter_xyz',
            ]);

            // ASSERT
            $this->assertEquals(0, $result->total());
        });
    }

    /**
     * Test: getRecruiters orders by created_at descending
     * Coverage: Line 20 (orderBy)
     */
    public function test_get_recruiters_orders_by_created_at_descending(): void
    {
        $this->tenant->run(function () {
            // ACT
            $result = $this->service->getRecruiters($this->tenant, [
                'per_page' => 10,
                'page' => 1,
                'search' => '',
            ]);

            // ASSERT - Check that results are ordered by created_at descending
            $items = $result->items();
            if (count($items) > 1) {
                for ($i = 0; $i < count($items) - 1; $i++) {
                    $this->assertGreaterThanOrEqual(
                        $items[$i + 1]->created_at,
                        $items[$i]->created_at
                    );
                }
            }
        });
    }

    /**
     * Test: getRecruiters includes tenantUsers relationship
     * Coverage: Line 17-19 (with tenantUsers)
     */
    public function test_get_recruiters_includes_tenant_users_relationship(): void
    {
        $this->tenant->run(function () {
            // ACT
            $result = $this->service->getRecruiters($this->tenant, [
                'per_page' => 10,
                'page' => 1,
                'search' => '',
            ]);

            // ASSERT - Check that tenantUsers relationship is loaded
            $items = $result->items();
            $this->assertGreaterThan(0, count($items));
            
            foreach ($items as $recruiter) {
                $this->assertNotNull($recruiter->tenantUsers);
                $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $recruiter->tenantUsers);
            }
        });
    }

    /**
     * Test: getRecruiters filters tenantUsers by tenant_id
     * Coverage: Line 18 (where tenant_id)
     */
    public function test_get_recruiters_filters_tenant_users_by_tenant_id(): void
    {
        $this->tenant->run(function () {
            // ACT
            $result = $this->service->getRecruiters($this->tenant, [
                'per_page' => 10,
                'page' => 1,
                'search' => '',
            ]);

            // ASSERT - Check that tenantUsers are filtered by tenant_id
            $items = $result->items();
            foreach ($items as $recruiter) {
                foreach ($recruiter->tenantUsers as $tenantUser) {
                    $this->assertEquals($this->tenant->id, $tenantUser->tenant_id);
                }
            }
        });
    }

    /**
     * Test: getRecruiters handles empty search parameter
     * Coverage: Line 14, 21 (when with empty search)
     */
    public function test_get_recruiters_handles_empty_search_parameter(): void
    {
        $this->tenant->run(function () {
            // ACT - Explicitly pass empty search
            $result = $this->service->getRecruiters($this->tenant, [
                'per_page' => 10,
                'page' => 1,
                'search' => '',
            ]);

            // ASSERT - Should return all recruiters without search filter
            $this->assertGreaterThanOrEqual(3, $result->total());
        });
    }

    /**
     * Test: getRecruiters handles null search parameter
     * Coverage: Line 14 (search ?? '')
     */
    public function test_get_recruiters_handles_null_search_parameter(): void
    {
        $this->tenant->run(function () {
            // ACT - Don't provide search parameter
            $result = $this->service->getRecruiters($this->tenant, [
                'per_page' => 10,
                'page' => 1,
            ]);

            // ASSERT - Should use default empty search
            $this->assertGreaterThanOrEqual(3, $result->total());
        });
    }

    /**
     * Test: getRecruiters search with special characters
     * Coverage: Line 22-26 (search with special chars)
     */
    public function test_get_recruiters_search_with_special_characters(): void
    {
        $this->tenant->run(function () {
            // ACT - Search with special characters (should not break query)
            $result = $this->service->getRecruiters($this->tenant, [
                'per_page' => 10,
                'page' => 1,
                'search' => '%_',
            ]);

            // ASSERT - Should not throw error and return results
            $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
        });
    }

    /**
     * Test: getRecruiters search is case-insensitive for mixed case input
     * Coverage: Line 22 (strtolower)
     */
    public function test_get_recruiters_search_is_case_insensitive_for_mixed_case(): void
    {
        $this->tenant->run(function () {
            // ACT - Search with mixed case
            $result = $this->service->getRecruiters($this->tenant, [
                'per_page' => 10,
                'page' => 1,
                'search' => 'JoHn DoE',
            ]);

            // ASSERT
            $this->assertGreaterThan(0, $result->total());
            $names = $result->pluck('name')->toArray();
            $this->assertContains('John Doe', $names);
        });
    }

    /**
     * Test: getRecruiters search matches both name and email
     * Coverage: Line 24-25 (orWhereRaw for email)
     */
    public function test_get_recruiters_search_matches_both_name_and_email(): void
    {
        $this->tenant->run(function () {
            // ACT - Search that could match either name or email
            $result = $this->service->getRecruiters($this->tenant, [
                'per_page' => 10,
                'page' => 1,
                'search' => 'smith',
            ]);

            // ASSERT - Should find Jane Smith by name
            $this->assertGreaterThan(0, $result->total());
            $names = $result->pluck('name')->toArray();
            $this->assertContains('Jane Smith', $names);
        });
    }
}
