<?php

namespace Tests\Feature\Central;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppConfigControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_slug_cooldown_config(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        config([
            'custom.portal_slug_change_cooldown_days' => 30,
            'custom.job_vacancy_slug_change_cooldown_days' => 30,
        ]);

        $response = $this->getJson('/api/app-config');

        $response->assertOk();
        $response->assertJsonPath('data.portal_slug_change_cooldown_days', 30);
        $response->assertJsonPath('data.job_vacancy_slug_change_cooldown_days', 30);
    }
}
