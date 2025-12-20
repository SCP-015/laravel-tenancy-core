<?php

namespace Tests\Unit\Models;

use App\Models\Tenant\JobLevel;
use Tests\Feature\TenantTestCase;

class JobLevelAuditTest extends TenantTestCase
{
    public function test_job_level_implements_auditable(): void
    {
        $model = new JobLevel();

        $this->assertInstanceOf(\OwenIt\Auditing\Contracts\Auditable::class, $model);
    }

    public function test_create_job_level_generates_audit_log(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\JobLevel::observe(\OwenIt\Auditing\AuditableObserver::class);

            $level = JobLevel::create([
                'name' => 'Senior',
                'index' => 0,
            ]);

            $level->refresh();

            $this->assertDatabaseHas('audits', [
                'auditable_type' => JobLevel::class,
                'auditable_id' => $level->id,
                'event' => 'created',
            ]);
        });
    }

    public function test_update_job_level_generates_audit_log(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\JobLevel::observe(\OwenIt\Auditing\AuditableObserver::class);

            $level = JobLevel::create([
                'name' => 'Old Level',
                'index' => 0,
            ]);

            $level->update([
                'name' => 'New Level',
            ]);

            $audit = $level->audits()->where('event', 'updated')->first();

            $this->assertNotNull($audit);
            $this->assertEquals('Old Level', $audit->old_values['name'] ?? null);
            $this->assertEquals('New Level', $audit->new_values['name'] ?? null);
        });
    }

    public function test_delete_job_level_generates_audit_log(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\JobLevel::observe(\OwenIt\Auditing\AuditableObserver::class);

            $level = JobLevel::create([
                'name' => 'To Delete',
                'index' => 0,
            ]);

            $levelId = $level->id;

            $level->delete();

            $this->assertDatabaseHas('audits', [
                'auditable_type' => JobLevel::class,
                'auditable_id' => $levelId,
                'event' => 'deleted',
            ]);
        });
    }

    public function test_update_index_generates_audit_log(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\JobLevel::observe(\OwenIt\Auditing\AuditableObserver::class);

            $level = JobLevel::create([
                'name' => 'Level',
                'index' => 0,
            ]);

            $level->update([
                'index' => 2,
            ]);

            $audit = $level->audits()->where('event', 'updated')->latest()->first();

            $this->assertNotNull($audit);
            $this->assertEquals(0, $audit->old_values['index'] ?? null);
            $this->assertEquals(2, $audit->new_values['index'] ?? null);
        });
    }

    public function test_sync_nusawork_generates_audit_log(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\JobLevel::observe(\OwenIt\Auditing\AuditableObserver::class);

            $level = JobLevel::create([
                'name' => 'Level',
                'index' => 0,
                'nusawork_id' => null,
                'nusawork_name' => null,
            ]);

            $level->update([
                'nusawork_id' => '67890',
                'nusawork_name' => 'Nusawork Level',
            ]);

            $audit = $level->audits()->where('event', 'updated')->latest()->first();

            $this->assertNotNull($audit);
            $this->assertNull($audit->old_values['nusawork_id'] ?? null);
            $this->assertEquals('67890', $audit->new_values['nusawork_id'] ?? null);
            $this->assertNull($audit->old_values['nusawork_name'] ?? null);
            $this->assertEquals('Nusawork Level', $audit->new_values['nusawork_name'] ?? null);
        });
    }

    public function test_unsync_nusawork_generates_audit_log(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\JobLevel::observe(\OwenIt\Auditing\AuditableObserver::class);

            $level = JobLevel::create([
                'name' => 'Level',
                'index' => 0,
                'nusawork_id' => '67890',
                'nusawork_name' => 'Nusawork Level',
            ]);

            $level->update([
                'nusawork_id' => null,
                'nusawork_name' => null,
            ]);

            $audit = $level->audits()->where('event', 'updated')->latest()->first();

            $this->assertNotNull($audit);
            $this->assertEquals('67890', $audit->old_values['nusawork_id'] ?? null);
            $this->assertNull($audit->new_values['nusawork_id'] ?? null);
            $this->assertEquals('Nusawork Level', $audit->old_values['nusawork_name'] ?? null);
            $this->assertNull($audit->new_values['nusawork_name'] ?? null);
        });
    }

    public function test_all_auditable_fields_are_tracked(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\JobLevel::observe(\OwenIt\Auditing\AuditableObserver::class);

            $level = JobLevel::create([
                'name' => 'Old Name',
                'index' => 0,
                'nusawork_id' => '111',
                'nusawork_name' => 'Old Nusawork',
            ]);

            $level->update([
                'name' => 'New Name',
                'index' => 5,
                'nusawork_id' => '222',
                'nusawork_name' => 'New Nusawork',
            ]);

            $audit = $level->audits()->where('event', 'updated')->latest()->first();

            $this->assertNotNull($audit);

            $expectedKeys = ['name', 'index', 'nusawork_id', 'nusawork_name'];

            foreach ($expectedKeys as $key) {
                $this->assertArrayHasKey($key, $audit->old_values ?? [], "Old values missing key {$key}");
                $this->assertArrayHasKey($key, $audit->new_values ?? [], "New values missing key {$key}");
            }

            $this->assertEquals('Old Name', $audit->old_values['name']);
            $this->assertEquals('New Name', $audit->new_values['name']);
            $this->assertEquals(0, $audit->old_values['index']);
            $this->assertEquals(5, $audit->new_values['index']);
            $this->assertEquals('111', $audit->old_values['nusawork_id']);
            $this->assertEquals('222', $audit->new_values['nusawork_id']);
            $this->assertEquals('Old Nusawork', $audit->old_values['nusawork_name']);
            $this->assertEquals('New Nusawork', $audit->new_values['nusawork_name']);
        });
    }

    public function test_generate_tags_returns_correct_tag(): void
    {
        $model = new JobLevel();

        $tags = $model->generateTags();

        $this->assertIsArray($tags);
        $this->assertContains('job-level', $tags);
    }

    public function test_audit_includes_user_id_when_authenticated(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\JobLevel::observe(\OwenIt\Auditing\AuditableObserver::class);

            $user = \App\Models\User::factory()->create();
            $this->actingAs($user);

            $level = JobLevel::create([
                'name' => 'With User',
                'index' => 0,
            ]);

            $audit = $level->audits()->first();

            $this->assertNotNull($audit);
            $this->assertEquals($user->id, $audit->user_id);
        });
    }
}
