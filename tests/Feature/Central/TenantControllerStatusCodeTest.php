<?php

namespace Tests\Feature\Central;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TenantControllerStatusCodeTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::findOrFail((string) $this->tenant->owner_id);

        $this->actingAs($this->user, 'api');
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function test_update_maps_status_to_http_status_code(): void
    {
        $mock = Mockery::mock('alias:App\\Services\\TenantService');
        $mock->shouldReceive('update')
            ->times(4)
            ->andReturn(
                ['status' => 'success'],
                ['status' => 'warning'],
                ['status' => 'forbidden'],
                ['status' => 'error']
            );

        $payload = [
            'name' => $this->tenant->name,
            'code' => $this->tenant->code,
            'enable_slug_history_redirect' => false,
        ];

        $this->postJson('/api/portal/' . $this->tenant->id, $payload)->assertStatus(200);
        $this->postJson('/api/portal/' . $this->tenant->id, $payload)->assertStatus(422);
        $this->postJson('/api/portal/' . $this->tenant->id, $payload)->assertStatus(403);
        $this->postJson('/api/portal/' . $this->tenant->id, $payload)->assertStatus(500);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function test_update_slug_maps_status_to_http_status_code(): void
    {
        $mock = Mockery::mock('alias:App\\Services\\TenantService');
        $mock->shouldReceive('updateSlug')
            ->times(4)
            ->andReturn(
                ['status' => 'success'],
                ['status' => 'warning'],
                ['status' => 'forbidden'],
                ['status' => 'error']
            );

        $this->postJson('/api/portal/' . $this->tenant->id . '/slug', ['slug' => 'status-slug-1'])->assertStatus(200);
        $this->postJson('/api/portal/' . $this->tenant->id . '/slug', ['slug' => 'status-slug-2'])->assertStatus(422);
        $this->postJson('/api/portal/' . $this->tenant->id . '/slug', ['slug' => 'status-slug-3'])->assertStatus(403);
        $this->postJson('/api/portal/' . $this->tenant->id . '/slug', ['slug' => 'status-slug-4'])->assertStatus(500);
    }
}
