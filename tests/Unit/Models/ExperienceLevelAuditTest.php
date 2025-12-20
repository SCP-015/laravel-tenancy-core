<?php

namespace Tests\Unit\Models;

use App\Models\Tenant\ExperienceLevel;
use Tests\Feature\TenantTestCase;

class ExperienceLevelAuditTest extends TenantTestCase
{
    public function test_experience_level_implements_auditable(): void
    {
        $model = new ExperienceLevel();

        $this->assertInstanceOf(\OwenIt\Auditing\Contracts\Auditable::class, $model);
    }

    public function test_create_experience_level_generates_audit_log(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\ExperienceLevel::observe(\OwenIt\Auditing\AuditableObserver::class);

            $level = ExperienceLevel::create([
                'name' => '3-5 Tahun',
                'index' => 0,
            ]);

            $level->refresh();

            $this->assertDatabaseHas('audits', [
                'auditable_type' => ExperienceLevel::class,
                'auditable_id' => $level->id,
                'event' => 'created',
            ]);
        });
    }

    public function test_update_experience_level_generates_audit_log(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\ExperienceLevel::observe(\OwenIt\Auditing\AuditableObserver::class);

            $level = ExperienceLevel::create([
                'name' => 'Old Experience',
                'index' => 0,
            ]);

            $level->update([
                'name' => 'New Experience',
            ]);

            $audit = $level->audits()->where('event', 'updated')->first();

            $this->assertNotNull($audit);
            $this->assertEquals('Old Experience', $audit->old_values['name'] ?? null);
            $this->assertEquals('New Experience', $audit->new_values['name'] ?? null);
        });
    }

    public function test_delete_experience_level_generates_audit_log(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\ExperienceLevel::observe(\OwenIt\Auditing\AuditableObserver::class);

            $level = ExperienceLevel::create([
                'name' => 'To Delete',
                'index' => 0,
            ]);

            $levelId = $level->id;

            $level->delete();

            $this->assertDatabaseHas('audits', [
                'auditable_type' => ExperienceLevel::class,
                'auditable_id' => $levelId,
                'event' => 'deleted',
            ]);
        });
    }

    public function test_update_index_generates_audit_log(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\ExperienceLevel::observe(\OwenIt\Auditing\AuditableObserver::class);

            $level = ExperienceLevel::create([
                'name' => 'Experience',
                'index' => 0,
            ]);

            $level->update([
                'index' => 1,
            ]);

            $audit = $level->audits()->where('event', 'updated')->latest()->first();

            $this->assertNotNull($audit);
            $this->assertEquals(0, $audit->old_values['index'] ?? null);
            $this->assertEquals(1, $audit->new_values['index'] ?? null);
        });
    }

    public function test_all_auditable_fields_are_tracked(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\ExperienceLevel::observe(\OwenIt\Auditing\AuditableObserver::class);

            $level = ExperienceLevel::create([
                'name' => 'Old Name',
                'index' => 0,
            ]);

            $level->update([
                'name' => 'New Name',
                'index' => 4,
            ]);

            $audit = $level->audits()->where('event', 'updated')->latest()->first();

            $this->assertNotNull($audit);

            $expectedKeys = ['name', 'index'];

            foreach ($expectedKeys as $key) {
                $this->assertArrayHasKey($key, $audit->old_values ?? [], "Old values missing key {$key}");
                $this->assertArrayHasKey($key, $audit->new_values ?? [], "New values missing key {$key}");
            }

            $this->assertEquals('Old Name', $audit->old_values['name']);
            $this->assertEquals('New Name', $audit->new_values['name']);
            $this->assertEquals(0, $audit->old_values['index']);
            $this->assertEquals(4, $audit->new_values['index']);
        });
    }

    public function test_generate_tags_returns_correct_tag(): void
    {
        $model = new ExperienceLevel();

        $tags = $model->generateTags();

        $this->assertIsArray($tags);
        $this->assertContains('experience-level', $tags);
    }

    public function test_audit_includes_user_id_when_authenticated(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\ExperienceLevel::observe(\OwenIt\Auditing\AuditableObserver::class);

            $user = \App\Models\User::factory()->create();
            $this->actingAs($user);

            $level = ExperienceLevel::create([
                'name' => 'With User',
                'index' => 0,
            ]);

            $audit = $level->audits()->first();

            $this->assertNotNull($audit);
            $this->assertEquals($user->id, $audit->user_id);
        });
    }
}
