<?php

namespace Tests\Feature\Central;

use App\Http\Resources\CompanyCategoryResource;
use App\Models\CompanyCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test untuk CompanyCategoryResource transformation
 */
class CompanyCategoryResourceTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test: Resource mengembalikan data dengan benar
     */
    public function test_resource_returns_data_correctly(): void
    {
        // ARRANGE
        $category = CompanyCategory::factory()->create([
            'name' => 'Teknologi Informasi',
            'description' => 'Perusahaan IT',
            'is_active' => true,
        ]);

        // ACT
        $resource = new CompanyCategoryResource($category);
        $array = $resource->toArray(request());

        // ASSERT
        $this->assertEquals($category->id, $array['id']);
        $this->assertEquals('Teknologi Informasi', $array['name']);
        $this->assertEquals('Perusahaan IT', $array['description']);
        $this->assertTrue($array['is_active']);
    }

    /**
     * Test: Resource mengembalikan semua atribut model
     */
    public function test_resource_returns_all_model_attributes(): void
    {
        // ARRANGE
        $category = CompanyCategory::factory()->create();

        // ACT
        $resource = new CompanyCategoryResource($category);
        $array = $resource->toArray(request());

        // ASSERT
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('is_active', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }

    /**
     * Test: Resource collection berfungsi dengan benar
     */
    public function test_resource_collection_works_correctly(): void
    {
        // ARRANGE
        $categories = CompanyCategory::factory()->count(3)->create();

        // ACT
        $collection = CompanyCategoryResource::collection($categories);
        $array = $collection->toArray(request());

        // ASSERT
        $this->assertCount(3, $array);
        $this->assertArrayHasKey('id', $array[0]);
        $this->assertArrayHasKey('name', $array[0]);
    }
}
