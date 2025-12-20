<?php

namespace Tests\Unit\Models;

use App\Http\Resources\Tenant\AuditLogResource;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Models\Audit;
use Tests\Feature\TenantTestCase;

class UserAuditTest extends TenantTestCase
{
    public function test_tenant_user_implements_auditable(): void
    {
        $model = new TenantUser();

        $this->assertInstanceOf(\OwenIt\Auditing\Contracts\Auditable::class, $model);
    }

    public function test_audit_log_resource_injects_name_and_email_for_user_role_change(): void
    {
        $this->tenant->run(function () {
            $userId = DB::connection('tenant')->table('users')->insertGetId([
                'name' => 'Audit User',
                'email' => 'audit-user@example.com',
                'password' => bcrypt('password'),
                'global_id' => uniqid(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $audit = new Audit([
                'auditable_type' => TenantUser::class,
                'auditable_id' => $userId,
                'event' => 'updated',
                'old_values' => ['role' => 'old-role'],
                'new_values' => ['role' => 'new-role'],
            ]);

            $resource = new AuditLogResource($audit);
            $request = Request::create('/test');
            $array = $resource->toArray($request);

            $this->assertArrayHasKey('role', $array['new_values']);
            $this->assertEquals('new-role', $array['new_values']['role']);

            $this->assertArrayHasKey('name', $array['new_values']);
            $this->assertEquals('Audit User', $array['new_values']['name']);

            $this->assertArrayHasKey('email', $array['new_values']);
            $this->assertEquals('audit-user@example.com', $array['new_values']['email']);
        });
    }
}
