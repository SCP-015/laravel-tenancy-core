<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\TenantResource;
use App\Models\CompanyCategory;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TenantResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_resource_returns_company_category_resource(): void
    {
        // Gunakan factory default apa adanya untuk menghindari bentrok slug unik.
        $category = CompanyCategory::factory()->create();

        $tenant = Tenant::factory()->create([
            'company_category_id' => $category->id,
        ]);

        $resource = new TenantResource($tenant->fresh('companyCategory'));
        $array = $resource->toArray(Request::create('/test'));

        // Sekarang company_category adalah Resource object, bukan string
        $this->assertArrayHasKey('company_category', $array);
        $this->assertInstanceOf(\App\Http\Resources\CompanyCategoryResource::class, $array['company_category']);
        
        // company_category_id juga harus ada sekarang
        $this->assertArrayHasKey('company_category_id', $array);
        $this->assertSame($category->id, $array['company_category_id']);

        // enable_slug_history_redirect harus ada dan berupa boolean
        $this->assertArrayHasKey('enable_slug_history_redirect', $array);
        $this->assertIsBool($array['enable_slug_history_redirect']);
    }
}
