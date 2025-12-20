<?php

namespace App\Console\Commands\User;

use App\Models\Tenant\User as TenantUser;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Console Command: MakeSuperAdmin
 * 
 * This command is excluded from code coverage because:
 * - Console commands are manually invoked CLI tools
 * - Better tested through manual testing or E2E tests
 * - Involves database operations and permission assignments
 * 
 * @codeCoverageIgnore
 */
class MakeSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:make-super-admin {--email=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ubah role user menjadi super_admin berdasarkan email';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->option('email');
        $result = $this->processSuperAdminAssignment($email);
        
        if ($result['success']) {
            $this->info($result['message']);
            return 0;
        } else {
            $this->error($result['message']);
            return 1;
        }
    }

    /**
     * Process super admin assignment logic
     * 
     * @param string $email
     * @return array
     */
    private function processSuperAdminAssignment($email)
    {
        // Cek apakah email valid
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => __('Invalid email format')
            ];
        }

        try {
            DB::beginTransaction();

            // Update user global
            $globalUser = User::where('email', $email)->first();

            if (!$globalUser) {
                return [
                    'success' => false,
                    'message' => __('User with email :email not found', ['email' => $email])
                ];
            }

            $globalUser->role = 'super_admin';
            $globalUser->save();

            $this->info('Berhasil mengupdate role global user menjadi super_admin');

            // Dapatkan daftar tenant yang dimiliki oleh user
            $tenants = $globalUser->tenants;

            // Update role di setiap tenant
            foreach ($tenants as $tenant) {
                $tenant->run(function () use ($email, $tenant) {
                    $tenantUser = TenantUser::where('email', $email)
                        ->where('tenant_id', $tenant->id)
                        ->first();

                    if (!$tenantUser) {
                        return;
                    }

                    $tenantUser->role = 'super_admin';
                    $tenantUser->save();
                    $tenantUser->syncRoles([$tenantUser->role]);

                    $this->info(
                        'Berhasil mengupdate role user di tenant ' . 
                        $tenant->name . ' (' . $tenant->id . ') menjadi super_admin'
                    );
                });
            }

            DB::commit();
            return [
                'success' => true,
                'message' => __('Process completed successfully!')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => __('An error occurred: :error', ['error' => $e->getMessage()])
            ];
        }
    }
}
