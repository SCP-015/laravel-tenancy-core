<?php

namespace Tests\Unit\Models;

use App\Models\Tenant;
use App\Models\TenantSlugHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantSlugHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_returns_belongs_to_relationship(): void
    {
        $tenant = Tenant::factory()->create();

        $history = TenantSlugHistory::create([
            'tenant_id' => $tenant->id,
            'slug' => 'some-old-slug',
        ]);

        $relation = $history->tenant();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
    }
}
