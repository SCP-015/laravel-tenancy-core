<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\User as CentralUser;
use App\Models\Tenant\User as TenantUser;

class TenantTestCase extends TestCase
{
    use DatabaseMigrations;

    protected Tenant $tenant;
    protected CentralUser $centralUser;
    protected CentralUser $centralUserRecruiter;

    /**
     * Metode ini akan berjalan secara otomatis sebelum setiap tes di kelas turunan.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set konfigurasi cache untuk testing
        config(['cache.default' => 'array']);
        config(['permission.cache.expiration_time' => 0]);

        // Clear cache permission
        app()['cache']->forget('spatie.permission.cache');

        // Boot auditing observers untuk enable auditing di tests
        // Auditing package tidak auto-boot observers di test environment
        $this->bootAuditingObservers();

        // Panggil helper untuk menyiapkan tenant
        $this->setupTenant();

        // Set role dan permission untuk user
        $this->afterApplicationCreated(function () {
            $this->artisan('cache:clear');
            $this->artisan('config:clear');
        });
    }
    
    /**
     * Boot auditing observers untuk semua auditable models
     * Diperlukan karena auditing package tidak auto-boot di test environment
     */
    protected function bootAuditingObservers(): void
    {
        // Get all models that implement Auditable
        $auditableModels = [
            \App\Models\Tenant\JobPosition::class,
            \App\Models\Tenant\JobLevel::class,
            \App\Models\Tenant\EducationLevel::class,
            \App\Models\Tenant\ExperienceLevel::class,
            // Add more auditable models here as needed
        ];
        
        foreach ($auditableModels as $model) {
            if (class_exists($model)) {
                $instance = new $model();
                if ($instance instanceof \OwenIt\Auditing\Contracts\Auditable) {
                    // Register the auditing observer
                    $model::observe(\OwenIt\Auditing\AuditableObserver::class);
                }
            }
        }
    }

    /**
     * Metode ini akan berjalan secara otomatis setelah setiap tes di kelas turunan.
     * Menghapus folder tenant storage yang dibuat selama testing.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        // Hapus folder tenant storage jika ada
        // Dilakukan setelah parent::tearDown() untuk memastikan semua resource sudah ditutup
        if ($this->tenant) {
            // Laravel Tenancy menyimpan folder di storage/testing/nusahire_{tenant_id}/ saat testing
            $tenantStoragePath = storage_path("testing/nusahire_{$this->tenant->id}");
            if (is_dir($tenantStoragePath)) {
                $this->deleteDirectory($tenantStoragePath);
            }
        }
    }

    /**
     * Helper untuk menghapus direktori secara rekursif
     */
    private function deleteDirectory(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }

        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $path . DIRECTORY_SEPARATOR . $file;
            if (is_dir($filePath)) {
                $this->deleteDirectory($filePath);
            } else {
                @unlink($filePath);
            }
        }

        return @rmdir($path);
    }

    /**
     * Helper untuk membuat tenant, pemiliknya, dan menginisialisasi konteks tenancy.
     * Menggunakan pola yang sama dengan NusaworkLoginService untuk konsistensi.
     */
    public function setupTenant(): void
    {
        // 1. Buat user sentral yang akan menjadi pemilik tenant menggunakan factory
        $this->centralUser = CentralUser::factory()->create();
        $this->centralUserRecruiter = CentralUser::factory()->create();

        // 2. Buat tenant baru menggunakan factory
        $this->tenant = Tenant::factory()->create([
            'owner_id' => $this->centralUser->id,
        ]);

        // 3. Setup users dengan role masing-masing di dalam tenant context
        $this->setupUserInTenant($this->centralUser, 'super_admin');
        $this->setupUserInTenant($this->centralUserRecruiter, 'admin');

        // 4. Inisialisasi tenancy
        tenancy()->initialize($this->tenant);
    }

    /**
     * Helper untuk membuat/update user di dalam tenant context
     *
     * @param CentralUser $centralUser
     * @param string $role
     * @return void
     */
    protected function setupUserInTenant(CentralUser $centralUser, string $role): void
    {
        // Setup di tenant database
        $this->tenant->run(function () use ($centralUser, $role) {
            $tenantUser = TenantUser::where('global_id', $centralUser->global_id)->first();

            if (!$tenantUser) {
                $tenantUser = TenantUser::create([
                    'global_id' => $centralUser->global_id,
                    'name' => $centralUser->name,
                    'email' => $centralUser->email,
                    'email_verified_at' => $centralUser->email_verified_at,
                    'password' => $centralUser->password,
                    'tenant_id' => $this->tenant->id,
                    'google_id' => $centralUser->google_id,
                    'nusawork_id' => $centralUser->nusawork_id,
                    'avatar' => $centralUser->avatar,
                    'role' => $role,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Assign role ke user tenant
            if ($tenantUser) {
                \Spatie\Permission\Models\Role::firstOrCreate([
                    'name' => $role,
                    'guard_name' => 'api',
                ]);

                $tenantUser->syncRoles([$role]);
            }
        });

        // Attach relasi user-tenant jika belum ada
        if (!$centralUser->tenants()->where('tenant_id', $this->tenant->id)->exists()) {
            $centralUser->tenants()->attach($this->tenant->id);
        }

        // Update central tenant_user record
        $tenantUserCentral = $centralUser->tenantUsers()->where('tenant_id', $this->tenant->id)->first();
        if ($tenantUserCentral) {
            $tenantUserCentral->update([
                'google_id' => $centralUser->google_id,
                'nusawork_id' => $centralUser->nusawork_id,
                'avatar' => $centralUser->avatar,
                'role' => $role,
                'tenant_join_date' => $tenantUserCentral->tenant_join_date ?? now(),
                'created_at' => $tenantUserCentral->created_at ?? now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Helper untuk login sebagai user pemilik tenant (super admin)
     *
     * @return \App\Models\User
     */
    public function actingAsTenantOwner(): CentralUser
    {
        $owner = $this->centralUser;

        if (!$owner) {
            $this->fail('Central owner user not available. Check the setUp() method in TenantTestCase.');
        }

        $this->actingAs($owner, 'api');

        return $owner;
    }

    /**
     * Helper untuk login sebagai recruiter
     *
     * @return \App\Models\User
     */
    public function actingAsRecruiter(): CentralUser
    {
        $recruiter = $this->centralUserRecruiter;

        if (!$recruiter) {
            $this->fail('Central recruiter user not available. Check the setUp() method in TenantTestCase.');
        }

        $this->actingAs($recruiter, 'api');

        return $recruiter;
    }

    /**
     * Helper untuk membuat tenant user baru dengan mudah
     * User akan otomatis ditambahkan ke tenant dan central database
     * Return object dengan property: centralUser dan tenantUser
     *
     * @param array $attributes
     * @return object{centralUser: CentralUser, tenantUser: TenantUser}
     */
    protected function createTenantUser(array $attributes = []): object
    {
        $globalId = \Illuminate\Support\Str::random(10);
        $name = $attributes['name'] ?? fake()->name();
        $email = $attributes['email'] ?? fake()->unique()->safeEmail();
        $password = $attributes['password'] ?? bcrypt('password');
        $role = $attributes['role'] ?? 'admin';

        // 1. Buat central user dulu (central tidak punya kolom role)
        $centralUser = CentralUser::create([
            'global_id' => $globalId,
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        // 2. Buat tenant user
        $tenantUser = TenantUser::create([
            'global_id' => $globalId,
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'tenant_id' => $this->tenant->id,
        ]);

        // 3. Attach ke tenant (check dulu agar tidak duplicate)
        if (!$centralUser->tenants()->where('tenant_id', $this->tenant->id)->exists()) {
            $centralUser->tenants()->attach($this->tenant->id);
        }

        return (object)[
            'centralUser' => $centralUser,
            'tenantUser' => $tenantUser,
        ];
    }
}
