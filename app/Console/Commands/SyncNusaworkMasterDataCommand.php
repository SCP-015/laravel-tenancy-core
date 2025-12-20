<?php

namespace App\Console\Commands;

use App\Jobs\SyncNusaworkMasterData;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Console Command: SyncNusaworkMasterDataCommand
 * 
 * This command is excluded from code coverage because:
 * - Console commands are manually invoked CLI tools
 * - Better tested through manual testing or E2E tests
 * - Involves external API synchronization
 * 
 * @codeCoverageIgnore
 */
class SyncNusaworkMasterDataCommand extends Command
{
    /**
     * Signature dari command.
     *
     * @var string
     */
    protected $signature = 'nusawork:sync-master-data
                            {user_id : ID dari user yang akan disinkronkan}
                            {--tenant_id= : ID tenant spesifik (opsional, jika tidak disediakan akan sync semua tenant)}';

    /**
     * Deskripsi dari command.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi master data Nusawork untuk user tertentu';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $tenantId = $this->option('tenant_id');

        // Validasi user exists
        $user = User::find($userId);
        if (!$user) {
            $this->error(__('User dengan ID :id tidak ditemukan', ['id' => $userId]));
            return Command::FAILURE;
        }

        // Validasi nusawork_id
        if (empty($user->nusawork_id)) {
            $this->error(__('User tidak memiliki Nusawork ID'));
            return Command::FAILURE;
        }

        // Dispatch job
        $this->info(__('Memulai sinkronisasi master data Nusawork...'));
        $this->info(__('User ID: :user_id', ['user_id' => $userId]));
        
        if ($tenantId) {
            $this->info(__('Tenant ID: :tenant_id', ['tenant_id' => $tenantId]));
        } else {
            $this->info(__('Akan mensinkronkan untuk semua tenant yang terintegrasi'));
        }

        SyncNusaworkMasterData::dispatch($user, $tenantId);

        $this->info(__('Job SyncNusaworkMasterData telah didispatch ke queue'));
        $this->info(__('Gunakan command "php artisan queue:work" untuk memproses job'));

        return Command::SUCCESS;
    }
}
