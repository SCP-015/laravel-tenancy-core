<?php

namespace Tests\Feature\Tenant;

use App\Mail\RecruiterInvitation;
use App\Mail\RecruiterRemoved;
use App\Models\Tenant;
use App\Models\Tenant\RecruiterInvitation as RecruiterInvitationModel;
use App\Models\Tenant\User as TenantUser;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\TenantTestCase;

/**
 * Test untuk RecruiterController
 */
class RecruiterControllerTest extends TenantTestCase
{
    protected $superAdmin;
    protected $recruiter;

    protected function setUp(): void
    {
        parent::setUp();

        // Set locale to English untuk testing
        app()->setLocale('en');

        // Ambil tenant users yang sudah dibuat dari parent
        $this->superAdmin = TenantUser::where('global_id', $this->centralUser->global_id)->first();
        $this->recruiter = TenantUser::where('global_id', $this->centralUserRecruiter->global_id)->first();

        // Create permissions yang dibutuhkan
        Permission::firstOrCreate(['name' => 'team.view', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'team.remove', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'team.manage_roles', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'team.invite', 'guard_name' => 'api']);

        // Assign permissions to super admin
        $this->superAdmin->givePermissionTo(['team.view', 'team.remove', 'team.manage_roles', 'team.invite']);
    }

    /**
     * Test: Index berhasil mendapatkan list recruiters
     */
    public function test_index_returns_recruiters_list_successfully(): void
    {
        // ACT - gunakan centralUser untuk actingAs
        $response = $this->actingAs($this->centralUser, 'api')
            ->getJson("/{$this->tenant->slug}/api/settings/recruiter");

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'email', 'role']
            ],
            'company_code',
            'is_super_admin',
        ]);
    }

    /**
     * Test: Index gagal tanpa permission
     */
    public function test_index_fails_without_permission(): void
    {
        // ARRANGE - user tanpa permission
        $users = $this->createTenantUser(['role' => 'admin']);
        $users->tenantUser->syncPermissions([]);

        // ACT
        $response = $this->actingAs($users->centralUser, 'api')
            ->getJson("/{$this->tenant->slug}/api/settings/recruiter");

        // ASSERT
        $response->assertStatus(403);
    }

    /**
     * Test: Destroy berhasil menghapus recruiter
     */
    public function test_destroy_removes_recruiter_successfully(): void
    {
        // ARRANGE
        Mail::fake();

        $recruiterToDelete = $this->createTenantUser(['role' => 'admin']);

        // ACT
        $response = $this->actingAs($this->centralUser, 'api')
            ->deleteJson("/{$this->tenant->slug}/api/settings/recruiter/{$recruiterToDelete->tenantUser->id}");

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'success',
        ]);

        // Verify user detached from tenant
        $this->assertFalse($this->tenant->users()->where('users.id', $recruiterToDelete->tenantUser->id)->exists());
    }

    /**
     * Test: Destroy mengirim email notification jika diminta
     */
    public function test_destroy_sends_email_notification_when_requested(): void
    {
        // ARRANGE
        Mail::fake();

        $recruiterToDelete = $this->createTenantUser(['email' => 'recruiter@example.com']);

        // ACT
        $response = $this->actingAs($this->centralUser, 'api')
            ->deleteJson("/{$this->tenant->slug}/api/settings/recruiter/{$recruiterToDelete->tenantUser->id}", [
                'notify_by_email' => true,
            ]);

        // ASSERT
        $response->assertStatus(200);
        Mail::assertQueued(RecruiterRemoved::class, function ($mail) use ($recruiterToDelete) {
            return $mail->hasTo($recruiterToDelete->tenantUser->email);
        });
    }

    /**
     * Test: Destroy gagal jika user not found
     */
    public function test_destroy_fails_when_user_not_found(): void
    {
        // ACT
        $response = $this->actingAs($this->centralUser, 'api')
            ->deleteJson("/{$this->tenant->slug}/api/settings/recruiter/99999");

        // ASSERT
        $response->assertStatus(404);
        $response->assertJsonFragment([
            'status' => 'warning',
        ]);
    }

    /**
     * Test: Destroy gagal jika mencoba delete diri sendiri
     */
    public function test_destroy_fails_when_deleting_self(): void
    {
        // ACT
        $response = $this->actingAs($this->centralUser, 'api')
            ->deleteJson("/{$this->tenant->slug}/api/settings/recruiter/{$this->centralUser->id}");

        // ASSERT
        $response->assertStatus(400);
        $response->assertJsonFragment([
            'status' => 'warning',
        ]);
    }

    /**
     * Test: Destroy gagal jika bukan super admin
     */
    public function test_destroy_fails_when_not_super_admin(): void
    {
        // ARRANGE
        $regularRecruiter = $this->createTenantUser(['role' => 'admin']);
        $recruiterToDelete = $this->createTenantUser();

        // ACT
        $response = $this->actingAs($regularRecruiter->centralUser, 'api')
            ->deleteJson("/{$this->tenant->slug}/api/settings/recruiter/{$recruiterToDelete->tenantUser->id}");

        // ASSERT
        $response->assertStatus(403);
    }

    /**
     * Test: UpdateRole berhasil mengubah role user
     */
    public function test_update_role_changes_user_role_successfully(): void
    {
        // ARRANGE
        $targetUser = $this->createTenantUser(['role' => 'admin']);

        // ACT
        $response = $this->actingAs($this->centralUser, 'api')
            ->putJson("/{$this->tenant->slug}/api/settings/recruiter/{$targetUser->tenantUser->id}/role", [
                'role' => 'super_admin',
            ]);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'success',
        ]);

        // Verify role updated
        $this->assertEquals('super_admin', $targetUser->tenantUser->fresh()->role);
    }

    /**
     * Test: UpdateRole gagal dengan invalid role
     */
    public function test_update_role_fails_with_invalid_role(): void
    {
        // ARRANGE
        $targetUser = $this->createTenantUser();

        // ACT
        $response = $this->actingAs($this->centralUser, 'api')
            ->putJson("/{$this->tenant->slug}/api/settings/recruiter/{$targetUser->tenantUser->id}/role", [
                'role' => 'invalid_role',
            ]);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['role']);
    }

    /**
     * Test: UpdateRole gagal jika user not found
     */
    public function test_update_role_fails_when_user_not_found(): void
    {
        // ACT
        $response = $this->actingAs($this->centralUser, 'api')
            ->putJson("/{$this->tenant->slug}/api/settings/recruiter/99999/role", [
                'role' => 'super_admin',
            ]);

        // ASSERT
        $response->assertStatus(404);
        $response->assertJsonFragment([
            'status' => 'warning',
        ]);
    }

    /**
     * Test: UpdateRole gagal jika mencoba ubah role sendiri
     */
    public function test_update_role_fails_when_changing_own_role(): void
    {
        // ACT
        $response = $this->actingAs($this->centralUser, 'api')
            ->putJson("/{$this->tenant->slug}/api/settings/recruiter/{$this->centralUser->id}/role", [
                'role' => 'admin',
            ]);

        // ASSERT
        $response->assertStatus(400);
        $response->assertJsonFragment([
            'status' => 'warning',
        ]);
    }

    /**
     * Test: UpdateRole gagal jika target adalah tenant owner
     */
    public function test_update_role_fails_when_target_is_tenant_owner(): void
    {
        // ARRANGE
        $ownerUser = $this->createTenantUser(['role' => 'super_admin']);
        
        // Set user as tenant owner
        $this->tenant->update(['owner_id' => $ownerUser->tenantUser->id]);

        // ACT - Try to change owner's role
        $response = $this->actingAs($this->centralUser, 'api')
            ->putJson("/{$this->tenant->slug}/api/settings/recruiter/{$ownerUser->tenantUser->id}/role", [
                'role' => 'admin',
            ]);

        // ASSERT
        $response->assertStatus(400);
        $response->assertJsonFragment([
            'status' => 'warning',
        ]);
    }

    /**
     * Test: UpdateRole gagal jika bukan super admin
     */
    public function test_update_role_fails_when_not_super_admin(): void
    {
        // ARRANGE
        $regularRecruiter = $this->createTenantUser(['role' => 'admin']);
        $targetUser = $this->createTenantUser();

        // ACT
        $response = $this->actingAs($regularRecruiter->centralUser, 'api')
            ->putJson("/{$this->tenant->slug}/api/settings/recruiter/{$targetUser->tenantUser->id}/role", [
                'role' => 'super_admin',
            ]);

        // ASSERT
        $response->assertStatus(403);
    }

    /**
     * Test: GetRoles berhasil mendapatkan list roles
     */
    public function test_get_roles_returns_roles_list_successfully(): void
    {
        // ACT
        $response = $this->actingAs($this->centralUser, 'api')
            ->getJson("/{$this->tenant->slug}/api/settings/roles");

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                '*' => ['id', 'name']
            ],
        ]);
    }

    /**
     * Test: Invite berhasil mengirim invitation
     */
    public function test_invite_sends_invitation_successfully(): void
    {
        // ARRANGE
        Mail::fake();
        $newEmail = 'newrecruiter@example.com';

        // ACT
        $response = $this->actingAs($this->centralUser, 'api')
            ->postJson("/{$this->tenant->slug}/api/settings/recruiter/invite", [
                'email' => $newEmail,
            ]);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'success',
        ]);

        // Verify email queued
        Mail::assertQueued(RecruiterInvitation::class, function ($mail) use ($newEmail) {
            return $mail->hasTo($newEmail);
        });

        // Verify invitation created
        $this->assertDatabaseHas('recruiter_invitations', [
            'email' => $newEmail,
            'status' => 'pending',
        ]);
    }

    /**
     * Test: Invite gagal jika email sudah terdaftar
     */
    public function test_invite_fails_when_email_already_exists(): void
    {
        // ARRANGE
        $existingUser = $this->createTenantUser(['email' => 'existing@example.com']);

        // ACT
        $response = $this->actingAs($this->centralUser, 'api')
            ->postJson("/{$this->tenant->slug}/api/settings/recruiter/invite", [
                'email' => $existingUser->tenantUser->email,
            ]);

        // ASSERT
        $response->assertStatus(400);
        $response->assertJsonFragment([
            'status' => 'warning',
        ]);
    }

    /**
     * Test: Invite gagal jika invitation masih pending
     */
    public function test_invite_fails_when_invitation_still_pending(): void
    {
        // ARRANGE
        Mail::fake();
        $email = 'pending@example.com';

        RecruiterInvitationModel::create([
            'email' => $email,
            'invited_by_email' => $this->centralUser->email,
            'code' => $this->tenant->code,
            'expires_at' => now()->addDays(5),
            'status' => 'pending',
        ]);

        // ACT
        $response = $this->actingAs($this->centralUser, 'api')
            ->postJson("/{$this->tenant->slug}/api/settings/recruiter/invite", [
                'email' => $email,
            ]);

        // ASSERT
        $response->assertStatus(400);
        $response->assertJsonFragment([
            'status' => 'warning',
        ]);
        $response->assertJsonPath('data.remaining_days', fn($days) => $days > 0);
    }

    /**
     * Test: Invite berhasil resend jika invitation expired
     */
    public function test_invite_resends_when_invitation_expired(): void
    {
        // ARRANGE
        Mail::fake();
        $email = 'expired@example.com';

        $expiredInvitation = RecruiterInvitationModel::create([
            'email' => $email,
            'invited_by_email' => 'old@example.com',
            'code' => $this->tenant->code,
            'expires_at' => now()->subDays(1), // Already expired
            'status' => 'pending',
        ]);

        // ACT
        $response = $this->actingAs($this->centralUser, 'api')
            ->postJson("/{$this->tenant->slug}/api/settings/recruiter/invite", [
                'email' => $email,
            ]);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'success',
        ]);

        // Verify email queued
        Mail::assertQueued(RecruiterInvitation::class);

        // Verify invitation updated (expires_at reset)
        $freshInvitation = $expiredInvitation->fresh();
        $this->assertTrue($freshInvitation->expires_at->isFuture());
    }

    /**
     * Test: Invite berhasil re-activate jika invitation sudah accepted
     */
    public function test_invite_reactivates_when_invitation_already_accepted(): void
    {
        // ARRANGE
        Mail::fake();
        $email = 'accepted@example.com';

        $acceptedInvitation = RecruiterInvitationModel::create([
            'email' => $email,
            'invited_by_email' => 'old@example.com',
            'code' => $this->tenant->code,
            'expires_at' => now()->subDays(1),
            'status' => 'accepted',
            'accepted_at' => now()->subDays(2),
        ]);

        // ACT
        $response = $this->actingAs($this->centralUser, 'api')
            ->postJson("/{$this->tenant->slug}/api/settings/recruiter/invite", [
                'email' => $email,
            ]);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'success',
        ]);

        // Pastikan tidak membuat record baru yang melanggar unique (email, code)
        $this->assertEquals(
            1,
            RecruiterInvitationModel::where('email', $email)
                ->where('code', $this->tenant->code)
                ->count()
        );

        $freshInvitation = $acceptedInvitation->fresh();
        $this->assertEquals('pending', $freshInvitation->status);
        $this->assertNull($freshInvitation->accepted_at);
        $this->assertTrue($freshInvitation->expires_at->isFuture());
        $this->assertEquals($this->centralUser->email, $freshInvitation->invited_by_email);
    }

    /**
     * Test: Invite gagal dengan invalid email
     */
    public function test_invite_fails_with_invalid_email(): void
    {
        // ACT
        $response = $this->actingAs($this->centralUser, 'api')
            ->postJson("/{$this->tenant->slug}/api/settings/recruiter/invite", [
                'email' => 'invalid-email',
            ]);

        // ASSERT
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Test: UpdateRole fails when trying to update user from different tenant
     */
    public function test_update_role_fails_for_user_from_different_tenant(): void
    {
        // ARRANGE - Create another tenant manually (without factory callbacks)
        $otherTenantOwner = User::factory()->create([
            'email' => 'owner@othercompany.com',
        ]);

        $otherTenant = new Tenant();
        $otherTenant->id = \Illuminate\Support\Str::ulid();
        $otherTenant->name = 'Other Company';
        $otherTenant->slug = 'other-company-' . time();
        $otherTenant->code = Tenant::generateCode();
        $otherTenant->owner_id = $otherTenantOwner->id;
        $otherTenant->save();

        // Create user and attach ONLY to other tenant
        $userInOtherTenant = User::factory()->create([
            'email' => 'user@othercompany.com',
        ]);

        // Detach from current tenant if auto-attached
        $this->tenant->users()->detach($userInOtherTenant->global_id);

        // Attach ONLY to other tenant using global_id (the relationship key)
        $otherTenant->users()->attach($userInOtherTenant->global_id, [
            'role' => 'admin',
        ]);

        // Refresh relationships to avoid cache
        $this->tenant->load('users');
        $otherTenant->load('users');

        // Verify user is in OTHER tenant
        $this->assertTrue(
            $otherTenant->users()->where('users.global_id', $userInOtherTenant->global_id)->exists(),
            'User should be in other tenant'
        );

        // Verify user is NOT in CURRENT tenant
        $this->assertFalse(
            $this->tenant->users()->where('users.global_id', $userInOtherTenant->global_id)->exists(),
            'User should NOT be in current tenant'
        );

        // ACT - Try to update role from CURRENT tenant (should fail)
        $response = $this->actingAs($this->centralUser, 'api')
            ->putJson("/{$this->tenant->slug}/api/settings/recruiter/{$userInOtherTenant->id}/role", [
                'role' => 'super_admin',
            ]);

        // ASSERT - Should return 404 because user is not in THIS tenant
        $response->assertStatus(404);
        $response->assertJsonFragment([
            'status' => 'warning',
            'message' => __('User is not a member of this tenant'),
        ]);
    }

    /**
     * Test: Destroy creates audit log with deleted_by
     */
    public function test_destroy_creates_audit_log_with_deleted_by(): void
    {
        // ARRANGE
        Mail::fake();
        $recruiterToDelete = $this->createTenantUser(['role' => 'admin']);

        // ACT
        $response = $this->actingAs($this->centralUser, 'api')
            ->deleteJson("/{$this->tenant->slug}/api/settings/recruiter/{$recruiterToDelete->tenantUser->id}");

        // ASSERT
        $response->assertStatus(200);

        // Check audit log exists
        $audit = \OwenIt\Auditing\Models\Audit::where('auditable_type', TenantUser::class)
            ->where('auditable_id', $recruiterToDelete->tenantUser->id)
            ->where('event', 'deleted')
            ->latest()
            ->first();

        $this->assertNotNull($audit, 'Audit log should be created');
        $this->assertEquals($this->superAdmin->id, $audit->user_id);
        $this->assertEquals('deleted', $audit->event);

        // Check old_values
        $this->assertArrayHasKey('role', $audit->old_values);
        $this->assertArrayHasKey('name', $audit->old_values);
        $this->assertArrayHasKey('email', $audit->old_values);

        // Check new_values has deleted_by
        $this->assertArrayHasKey('deleted_by', $audit->new_values);
        $this->assertIsArray($audit->new_values['deleted_by']);
        $this->assertEquals($this->superAdmin->name, $audit->new_values['deleted_by']['name']);
        $this->assertEquals($this->superAdmin->email, $audit->new_values['deleted_by']['email']);
    }

    /**
     * Test: UpdateRole creates audit log with changed_by
     */
    public function test_update_role_creates_audit_log_with_changed_by(): void
    {
        // ARRANGE
        $recruiterToUpdate = $this->createTenantUser(['role' => 'admin']);

        // ACT
        $response = $this->actingAs($this->centralUser, 'api')
            ->putJson("/{$this->tenant->slug}/api/settings/recruiter/{$recruiterToUpdate->tenantUser->id}/role", [
                'role' => 'super_admin',
            ]);

        // ASSERT
        $response->assertStatus(200);

        // Check audit log exists
        $audit = \OwenIt\Auditing\Models\Audit::where('auditable_type', TenantUser::class)
            ->where('auditable_id', $recruiterToUpdate->tenantUser->id)
            ->where('event', 'updated')
            ->where('tags', 'role_updated')
            ->latest()
            ->first();

        $this->assertNotNull($audit, 'Audit log should be created');
        $this->assertEquals($this->superAdmin->id, $audit->user_id);
        $this->assertEquals('updated', $audit->event);

        // Check old_values
        $this->assertArrayHasKey('role', $audit->old_values);
        $this->assertEquals('admin', $audit->old_values['role']);

        // Check new_values
        $this->assertArrayHasKey('role', $audit->new_values);
        $this->assertEquals('super_admin', $audit->new_values['role']);

        // Check new_values has changed_by
        $this->assertArrayHasKey('changed_by', $audit->new_values);
        $this->assertIsArray($audit->new_values['changed_by']);
        $this->assertEquals($this->superAdmin->name, $audit->new_values['changed_by']['name']);
        $this->assertEquals($this->superAdmin->email, $audit->new_values['changed_by']['email']);
    }
}
