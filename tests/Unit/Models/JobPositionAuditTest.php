<?php

namespace Tests\Unit\Models;

use App\Models\Tenant\JobPosition;
use Tests\Feature\TenantTestCase;

class JobPositionAuditTest extends TenantTestCase
{
    public function test_job_position_implements_auditable(): void
    {
        $model = new JobPosition();

        $this->assertInstanceOf(\OwenIt\Auditing\Contracts\Auditable::class, $model);
    }

    public function test_create_job_position_generates_audit_log(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\JobPosition::observe(\OwenIt\Auditing\AuditableObserver::class);

            $position = JobPosition::create([
                'name' => 'Software Engineer',
            ]);

            $position->refresh();

            $this->assertDatabaseHas('audits', [
                'auditable_type' => JobPosition::class,
                'auditable_id' => $position->id,
                'event' => 'created',
            ]);
        });
    }

    public function test_update_job_position_generates_audit_log(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\JobPosition::observe(\OwenIt\Auditing\AuditableObserver::class);

            $position = JobPosition::create([
                'name' => 'Old Position',
            ]);

            $position->update([
                'name' => 'New Position',
            ]);

            $audit = $position->audits()->where('event', 'updated')->first();

            $this->assertNotNull($audit);
            $this->assertEquals('Old Position', $audit->old_values['name'] ?? null);
            $this->assertEquals('New Position', $audit->new_values['name'] ?? null);
        });
    }

    public function test_delete_job_position_generates_audit_log(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\JobPosition::observe(\OwenIt\Auditing\AuditableObserver::class);

            $position = JobPosition::create([
                'name' => 'To Delete',
            ]);

            $positionId = $position->id;

            $position->delete();

            $this->assertDatabaseHas('audits', [
                'auditable_type' => JobPosition::class,
                'auditable_id' => $positionId,
                'event' => 'deleted',
            ]);
        });
    }

    public function test_update_parent_generates_audit_log(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\JobPosition::observe(\OwenIt\Auditing\AuditableObserver::class);

            $parent1 = JobPosition::create(['name' => 'Parent 1']);
            $parent2 = JobPosition::create(['name' => 'Parent 2']);

            $position = JobPosition::create([
                'name' => 'Child Position',
                'id_parent' => $parent1->id,
            ]);

            $position->update([
                'id_parent' => $parent2->id,
            ]);

            $audit = $position->audits()->where('event', 'updated')->latest()->first();

            $this->assertNotNull($audit);
            $this->assertEquals($parent1->id, $audit->old_values['id_parent'] ?? null);
            $this->assertEquals($parent2->id, $audit->new_values['id_parent'] ?? null);
        });
    }

    public function test_sync_nusawork_generates_audit_log(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\JobPosition::observe(\OwenIt\Auditing\AuditableObserver::class);

            $position = JobPosition::create([
                'name' => 'Position',
                'nusawork_id' => null,
                'nusawork_name' => null,
            ]);

            $position->update([
                'nusawork_id' => '12345',
                'nusawork_name' => 'Nusawork Position',
            ]);

            $audit = $position->audits()->where('event', 'updated')->latest()->first();

            $this->assertNotNull($audit);
            $this->assertNull($audit->old_values['nusawork_id'] ?? null);
            $this->assertEquals('12345', $audit->new_values['nusawork_id'] ?? null);
            $this->assertNull($audit->old_values['nusawork_name'] ?? null);
            $this->assertEquals('Nusawork Position', $audit->new_values['nusawork_name'] ?? null);
        });
    }

    public function test_unsync_nusawork_generates_audit_log(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\JobPosition::observe(\OwenIt\Auditing\AuditableObserver::class);

            $position = JobPosition::create([
                'name' => 'Position',
                'nusawork_id' => '12345',
                'nusawork_name' => 'Nusawork Position',
            ]);

            $position->update([
                'nusawork_id' => null,
                'nusawork_name' => null,
            ]);

            $audit = $position->audits()->where('event', 'updated')->latest()->first();

            $this->assertNotNull($audit);
            $this->assertEquals('12345', $audit->old_values['nusawork_id'] ?? null);
            $this->assertNull($audit->new_values['nusawork_id'] ?? null);
            $this->assertEquals('Nusawork Position', $audit->old_values['nusawork_name'] ?? null);
            $this->assertNull($audit->new_values['nusawork_name'] ?? null);
        });
    }

    public function test_all_auditable_fields_are_tracked(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\JobPosition::observe(\OwenIt\Auditing\AuditableObserver::class);

            $parent1 = JobPosition::create(['name' => 'Parent 1']);
            $parent2 = JobPosition::create(['name' => 'Parent 2']);

            $position = JobPosition::create([
                'name' => 'Old Name',
                'id_parent' => $parent1->id,
                'nusawork_id' => '111',
                'nusawork_name' => 'Old Nusawork',
            ]);

            $position->update([
                'name' => 'New Name',
                'id_parent' => $parent2->id,
                'nusawork_id' => '222',
                'nusawork_name' => 'New Nusawork',
            ]);

            $audit = $position->audits()->where('event', 'updated')->latest()->first();

            $this->assertNotNull($audit);

            $expectedKeys = ['name', 'id_parent', 'nusawork_id', 'nusawork_name'];

            foreach ($expectedKeys as $key) {
                $this->assertArrayHasKey($key, $audit->old_values ?? [], "Old values missing key {$key}");
                $this->assertArrayHasKey($key, $audit->new_values ?? [], "New values missing key {$key}");
            }

            $this->assertEquals('Old Name', $audit->old_values['name']);
            $this->assertEquals('New Name', $audit->new_values['name']);
            $this->assertEquals($parent1->id, $audit->old_values['id_parent']);
            $this->assertEquals($parent2->id, $audit->new_values['id_parent']);
            $this->assertEquals('111', $audit->old_values['nusawork_id']);
            $this->assertEquals('222', $audit->new_values['nusawork_id']);
            $this->assertEquals('Old Nusawork', $audit->old_values['nusawork_name']);
            $this->assertEquals('New Nusawork', $audit->new_values['nusawork_name']);
        });
    }

    public function test_generate_tags_returns_correct_tag(): void
    {
        $model = new JobPosition();

        $tags = $model->generateTags();

        $this->assertIsArray($tags);
        $this->assertContains('job-position', $tags);
    }

    public function test_audit_includes_user_id_when_authenticated(): void
    {
        $this->tenant->run(function () {
            \App\Models\Tenant\JobPosition::observe(\OwenIt\Auditing\AuditableObserver::class);

            $user = \App\Models\User::factory()->create();
            $this->actingAs($user);

            $position = JobPosition::create([
                'name' => 'With User',
            ]);

            $audit = $position->audits()->first();

            $this->assertNotNull($audit);
            $this->assertEquals($user->id, $audit->user_id);
        });
    }
}
