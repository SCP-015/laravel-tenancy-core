<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant\JobPosition;
use Tests\Feature\TenantTestCase;

/**
 * Test untuk JobPosition Model
 * 
 * Coverage target:
 * - Line 24: parent() belongsTo relation
 */
class JobPositionModelTest extends TenantTestCase
{
    /**
     * Test: parent() relation mengembalikan parent JobPosition yang benar
     * 
     * Cover line 24: return $this->belongsTo(self::class, 'id_parent');
     */
    public function test_parent_relation_returns_correct_parent_position(): void
    {
        // ARRANGE - Buat parent dan child job positions
        $parentPosition = JobPosition::create([
            'name' => 'Engineering',
            'nusawork_id' => null,
            'nusawork_name' => null,
            'id_parent' => null,
        ]);
        
        $childPosition = JobPosition::create([
            'name' => 'Software Engineer',
            'nusawork_id' => null,
            'nusawork_name' => null,
            'id_parent' => $parentPosition->id,
        ]);

        // ACT
        $parent = $childPosition->parent;

        // ASSERT
        $this->assertNotNull($parent);
        $this->assertInstanceOf(JobPosition::class, $parent);
        $this->assertEquals($parentPosition->id, $parent->id);
        $this->assertEquals('Engineering', $parent->name);
    }

    /**
     * Test: parent() relation returns null when no parent
     */
    public function test_parent_relation_returns_null_when_no_parent(): void
    {
        // ARRANGE - Job position tanpa parent
        $position = JobPosition::create([
            'name' => 'Top Level Position',
            'nusawork_id' => null,
            'nusawork_name' => null,
            'id_parent' => null,
        ]);

        // ACT
        $parent = $position->parent;

        // ASSERT
        $this->assertNull($parent);
    }

    /**
     * Test: children() relation returns correct child positions
     */
    public function test_children_relation_returns_correct_children(): void
    {
        // ARRANGE
        $parent = JobPosition::create([
            'name' => 'IT Department',
            'nusawork_id' => null,
            'nusawork_name' => null,
            'id_parent' => null,
        ]);
        
        $child1 = JobPosition::create([
            'name' => 'Backend Developer',
            'nusawork_id' => null,
            'nusawork_name' => null,
            'id_parent' => $parent->id,
        ]);
        
        $child2 = JobPosition::create([
            'name' => 'Frontend Developer',
            'nusawork_id' => null,
            'nusawork_name' => null,
            'id_parent' => $parent->id,
        ]);

        // ACT
        $children = $parent->children;

        // ASSERT
        $this->assertCount(2, $children);
        $childIds = $children->pluck('id')->toArray();
        $this->assertContains($child1->id, $childIds);
        $this->assertContains($child2->id, $childIds);
    }
}
