<?php

namespace Tests\Feature\Tenant;

use Tests\Feature\TenantTestCase;

class AppConfigControllerTest extends TenantTestCase
{
    public function test_show_returns_slug_cooldown_config_in_tenant_api(): void
    {
        $this->actingAsTenantOwner();

        config([
            'custom.portal_slug_change_cooldown_days' => 30,
            'custom.job_vacancy_slug_change_cooldown_days' => 30,
        ]);

        $response = $this->getJson('/' . $this->tenant->id . '/api/app-config');

        $response->assertOk();
        $response->assertJsonPath('data.portal_slug_change_cooldown_days', 30);
        $response->assertJsonPath('data.job_vacancy_slug_change_cooldown_days', 30);
    }
}
