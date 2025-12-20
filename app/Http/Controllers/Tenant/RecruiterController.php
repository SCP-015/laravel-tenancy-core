<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\InviteRecruiterRequest;
use App\Http\Requests\Tenant\UpdateRecruiterRoleRequest;
use App\Http\Resources\Tenant\RecruiterResource;
use App\Mail\RecruiterInvitation;
use App\Mail\RecruiterRemoved;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Tenant\RecruiterInvitation as RecruiterInvitationModel;
use App\Services\Tenant\RecruiterService;
use App\Services\TenantUserAuditService;
use App\Traits\HasPermissionTrait;
use App\Traits\Loggable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class RecruiterController extends Controller
{
    use HasPermissionTrait;
    use Loggable;

    protected $recruiterService;

    public function __construct(RecruiterService $recruiterService)
    {
        $this->recruiterService = $recruiterService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->checkPermission('team.view');

        $user = $request->user();
        $tenant = tenant();

        $isSuperAdmin = $user->isSuperAdmin();

        $recruiters = $this->recruiterService->getRecruiters($tenant, $request->all());

        return RecruiterResource::collection($recruiters)->additional([
            'company_code' => $tenant->code,
            'is_super_admin' => $isSuperAdmin,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $this->checkPermission('team.remove');

        $isNotifyByEmail = $request->input('notify_by_email', false);
        $recruiterId = $request->route('recruiter');
        $recruiter = User::find($recruiterId);
        if (!$recruiter) {
            return response()->json([
                'status' => 'warning',
                'message' => __('User not found'),
            ], 404);
        }

        $cUser = $request->user();
        if ($cUser->id == $recruiter->id) {
            return response()->json([
                'status' => 'warning',
                'message' => __('You cannot delete yourself'),
            ], 400);
        }

        // Get tenant for email notification
        $tenant = tenant();
        $receiverMail = $recruiter->email;
        $tenantName = $tenant->name;

        // Create audit log untuk delete recruiter di tenant context
        $tenant->run(function () use ($recruiter, $cUser) {
            $tenantUser = \App\Models\Tenant\User::find($recruiter->id);
            
            if ($tenantUser) {
                // Disable auditing otomatis
                $tenantUser->disableAuditing();
                
                // Prepare old_values untuk audit log
                $oldValues = [
                    'role' => $tenantUser->role,
                    'name' => $tenantUser->name,
                    'email' => $tenantUser->email,
                ];
                
                // Prepare new_values dengan deleted_by (sama seperti changed_by pada update role)
                $newValues = [
                    'role' => $tenantUser->role,
                    'name' => $tenantUser->name,
                    'email' => $tenantUser->email,
                    'deleted_by' => [
                        'name' => $cUser->name,
                        'email' => $cUser->email,
                    ],
                ];

                // Create manual audit log dengan event 'deleted'
                TenantUserAuditService::logRecruiterDeleted($cUser, $tenantUser, $oldValues, $newValues);
            }
        });

        // Detach the user from the specific tenant
        $recruiter->tenants()->detach($tenant->id);

        // Kirim email secara async menggunakan queue untuk menghindari blocking
        if ($isNotifyByEmail) {
            Mail::to($receiverMail)
                ->queue(new RecruiterRemoved($cUser->email, $receiverMail, $tenantName));
        }

        return response()->json([
            'status' => 'success',
            'message' => __('User deleted successfully'),
        ]);
    }

    /**
     * Update user role
     */
    public function updateRole(UpdateRecruiterRoleRequest $request)
    {
        $this->checkPermission('team.manage_roles');

        $input = $request->validated();

        $userId = $request->route('recruiter');
        $targetUser = User::find($userId);

        if (!$targetUser) {
            return response()->json([
                'status' => 'warning',
                'message' => __('User not found'),
            ], 404);
        }

        $currentUser = $request->user();
        $tenant = tenant();

        // Cek apakah target user ada di tenant ini
        $targetUserInTenant = $tenant->users->where('id', $targetUser->id)->first();
        if (!$targetUserInTenant) {
            return response()->json([
                'status' => 'warning',
                'message' => __('User is not a member of this tenant'),
            ], 404);
        }

        // Tidak bisa mengubah role diri sendiri
        if ($currentUser->id == $targetUser->id) {
            return response()->json([
                'status' => 'warning',
                'message' => __('You cannot change your own role'),
            ], 400);
        }

        // Tidak bisa mengubah role user pemilik tenant
        if ($targetUser->id == $tenant->owner_id) {
            return response()->json([
                'status' => 'warning',
                'message' => __('You cannot change the role of the tenant owner'),
            ], 400);
        }

        // Update role di pivot table (central tenant_users)
        $centralTenantUser = $targetUser->tenantUsers()->where('tenant_id', $tenant->id)->first();
        if ($centralTenantUser) {
            $centralTenantUser->update(['role' => $input['role']]);
        }

        // Update role di tenant database
        $tenant->run(function () use ($targetUser, $input, $currentUser) {
            $tenantUser = \App\Models\Tenant\User::find($targetUser->id);

            if ($tenantUser) {
                // Simpan old values sebelum update
                $oldValues = [
                    'role' => $tenantUser->role,
                    'name' => $tenantUser->name,
                    'email' => $tenantUser->email,
                ];
                
                // Disable auditing otomatis
                $tenantUser->disableAuditing();
                
                // Update role
                $tenantUser->update(['role' => $input['role']]);
                
                // Enable auditing kembali
                $tenantUser->enableAuditing();

                // Hapus semua role yang ada
                $tenantUser->roles()->detach();

                // Assign role baru
                $tenantUser->assignRole($tenantUser->role);
                
                // Prepare new_values dengan changed_by
                $newValues = [
                    'role' => $tenantUser->role,
                    'name' => $tenantUser->name,
                    'email' => $tenantUser->email,
                    'changed_by' => [
                        'name' => $currentUser->name,
                        'email' => $currentUser->email,
                    ],
                ];

                // Create manual audit log dengan event 'updated'
                TenantUserAuditService::logRoleUpdated($currentUser, $tenantUser, $oldValues, $newValues);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => __('User role updated successfully'),
            'data' => [
                'user_id' => $targetUser->id,
                'new_role' => $input['role'],
            ]
        ]);
    }

    /**
     * Get available roles
     */
    public function getRoles(Request $request)
    {
        $this->checkPermission('team.view');

        $tenant = tenant();

        $roles = [];
        $tenant->run(function () use (&$roles) {
            $roles = \Spatie\Permission\Models\Role::all(['id', 'name'])->toArray();
        });

        return response()->json([
            'status' => 'success',
            'data' => $roles,
        ]);
    }

    /**
     * Invite a new recruiter
     */
    public function invite(InviteRecruiterRequest $request)
    {
        $this->checkPermission('team.invite');

        $input = $request->validated();

        $user = $request->user();
        $tenant = tenant();
        $invitationCode = $tenant->code;

        // Tambahkan validasi ketika email yang dinput sudah ada di users tenant
        $existingUser = $tenant->users()->where('email', $input['email'])->first();
        if ($existingUser) {
            return response()->json([
                'status' => 'warning',
                'message' => __('Email :email already registered as an admin in this portal', ['email' => $input['email']]),
            ], 400);
        }

        // Cek apakah invitation untuk email ini sudah pernah ada.
        // Penting: code saat ini memakai $tenant->code (kode company), sehingga untuk email yang sama
        // tidak boleh membuat record baru karena ada unique constraint (email, code).
        $existingInvitation = RecruiterInvitationModel::where('email', $input['email'])
            ->where('code', $invitationCode)
            ->first();

        if ($existingInvitation) {
            // Jika undangan masih pending dan belum expired, jangan kirim ulang
            if ($existingInvitation->status === 'pending' && ! $existingInvitation->isExpired()) {
                // Hitung sisa hari dan bulatkan ke atas
                $remainingDays = now()->diffInHours($existingInvitation->expires_at) / 24;
                $remainingDays = ceil($remainingDays);

                return response()->json([
                    'status' => 'warning',
                    'message' => __('An invitation has already been sent to :email and is still valid for :days more days', [
                        'email' => $input['email'],
                        'days' => $remainingDays,
                    ]),
                    'data' => [
                        'invitation_id' => $existingInvitation->id,
                        'code' => $existingInvitation->code,
                        'expires_at' => $existingInvitation->expires_at,
                        'remaining_days' => $remainingDays,
                    ],
                ], 400);
            }

            // Jika invitation sudah accepted / expired, atau pending tapi expired, aktifkan ulang
            $existingInvitation->update([
                'status' => 'pending',
                'expires_at' => now()->addDays(7),
                'invited_by_email' => $user->email,
                'accepted_at' => null,
            ]);

            $invitation = $existingInvitation;
        } else {
            // Jika belum ada undangan, buat undangan baru
            $invitation = RecruiterInvitationModel::create([
                'email' => $input['email'],
                'invited_by_email' => $user->email,
                'code' => $invitationCode,
                'expires_at' => now()->addDays(7),
                'status' => 'pending',
                'accepted_at' => null,
            ]);
        }

        // Generate invite link dengan kode unik
        $inviteUrl = url("/auth/{$tenant->slug}/invite-recruiter?code={$invitationCode}");

        // Kirim email undangan secara async menggunakan queue untuk menghindari blocking
        Mail::to($input['email'])
            ->queue(new RecruiterInvitation($user->email, $input['email'], $tenant->name, $invitationCode, $inviteUrl));

        return response()->json([
            'status' => 'success',
            'message' => __('Invitation sent successfully'),
            'data' => [
                'invitation_id' => $invitation->id,
                'code' => $invitation->code,
                'expires_at' => $invitation->expires_at,
            ],
        ]);
    }
}
