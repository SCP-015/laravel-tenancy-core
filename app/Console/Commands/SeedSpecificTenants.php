<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Console Command: SeedSpecificTenants
 * 
 * This command is excluded from code coverage because:
 * - Console commands are manually invoked CLI tools
 * - Better tested through manual testing or E2E tests
 * - Involves database seeding operations
 * 
 * @codeCoverageIgnore
 */
class SeedSpecificTenants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:seed-specific 
                            {--tenants= : Comma-separated list of tenant IDs}
                            {--class= : The seeder class to run}
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed specific tenants by ID';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantIdentifiers = $this->option('tenants');
        $seederClass = $this->option('class') ?: '\Database\Seeders\TenantDatabaseSeeder';
        $force = $this->option('force');

        if (!$tenantIdentifiers) {
            $this->error(__('Please provide tenant IDs using --tenants option'));
            $this->info(__('Example: php artisan tenants:seed-specific --tenants=68ce19bb1bf15,another-tenant-id'));
            return 1;
        }

        $identifiers = array_map('trim', explode(',', $tenantIdentifiers));
        $tenants = collect();

        // Cari tenant berdasarkan ID
        foreach ($identifiers as $identifier) {
            // Cari berdasarkan ID
            $tenant = Tenant::find($identifier);

            if ($tenant) {
                $tenants->push($tenant);
                $this->info(__('Found tenant: :name (ID: :id)', [
                    'name' => $tenant->name,
                    'id' => $tenant->id
                ]));
            } else {
                $this->warn(__('Tenant not found: :identifier', ['identifier' => $identifier]));
            }
        }

        if ($tenants->isEmpty()) {
            $this->error(__('No valid tenants found'));
            return 1;
        }

        $this->info(__('Starting seeding for :count tenant(s)...', ['count' => $tenants->count()]));

        foreach ($tenants as $tenant) {
            $this->info(__('Seeding tenant: :name', ['name' => $tenant->name]));
            
            try {
                $tenant->run(function () use ($seederClass, $force) {
                    $params = [
                        '--class' => $seederClass,
                    ];
                    
                    if ($force) {
                        $params['--force'] = true;
                    }
                    
                    Artisan::call('db:seed', $params);
                });
                
                $this->info(__('✓ Successfully seeded tenant: :name', ['name' => $tenant->name]));
            } catch (\Exception $e) {
                $this->error(__('✗ Failed to seed tenant :name: :error', [
                    'name' => $tenant->name,
                    'error' => $e->getMessage()
                ]));
            }
        }

        $this->info(__('Seeding completed!'));
        return 0;
    }
}
