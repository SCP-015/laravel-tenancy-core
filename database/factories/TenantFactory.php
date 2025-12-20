<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use App\Services\UIDGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tenant::class;


    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => UIDGenerator::generate($this->model),
            'name' => $this->faker->name(),
            'slug' => $this->faker->slug(),
            'code' => Tenant::generateCode(),
            'plan' => 'free',
            'owner_id' => User::factory(),
        ];
    }

    /**
     * Konfigurasi state setelah model Tenant dibuat.
     * Di sinilah semua keajaiban terjadi.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Tenant $tenant) {
            // 1. Dapatkan instance User pemilik yang baru saja dibuat oleh factory.
            $owner = User::find($tenant->owner_id);

            if ($owner) {
                // 2. Sesuai permintaan Anda: Update kolom 'global_id' di tabel 'users' sentral
                //    dengan nilai dari ID tenant yang baru dibuat.
                // $owner->update([
                //     'global_id' => $tenant->id,
                // ]);

                // 3. Hubungkan user ke tenant. Perintah 'attach' ini sekarang akan:
                //    - Membuat baris baru di tabel pivot 'tenant_users'.
                //    - Mengisi 'tenant_id' dengan $tenant->id.
                //    - Mengisi 'global_user_id' dengan nilai dari 'global_id' milik si owner,
                //      yang mana sudah kita update menjadi sama dengan $tenant->id.
                //    Ini akan memenuhi kondisi Anda di mana tenant_id dan global_user_id memiliki nilai yang sama.
                // 1. Update tenant user dalam konteks tenant dulu (untuk memastikan user ada di tenant DB)
                $tenant->run(function () use ($owner, $tenant) {
                    // Cari user berdasarkan global_id untuk menghindari conflict
                    $tenantUser = \App\Models\Tenant\User::where('global_id', $owner->global_id)->first();
                    
                    if ($tenantUser) {
                        // Update existing tenant user
                        $tenantUser->update([
                            'tenant_id' => $tenant->id,
                            'role' => 'super_admin',
                            'updated_at' => now(),
                        ]);
                    } else {
                        // Buat user baru di tenant context jika belum ada
                        // Tidak menggunakan ID yang sama untuk menghindari conflict
                        $tenantUser = \App\Models\Tenant\User::create([
                            'global_id' => $owner->global_id,
                            'name' => $owner->name,
                            'email' => $owner->email,
                            'email_verified_at' => $owner->email_verified_at,
                            'password' => $owner->password,
                            'tenant_id' => $tenant->id,
                            'google_id' => $owner->google_id,
                            'nusawork_id' => $owner->nusawork_id,
                            'avatar' => $owner->avatar,
                            'role' => 'super_admin',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    // Assign role ke user tenant
                    if ($tenantUser) {
                        $tenantUser->syncRoles(['super_admin']);
                    }
                });

                // 2. Attach relation jika belum ada (dengan pengecekan)
                if (!$owner->tenants()->where('tenant_id', $tenant->id)->exists()) {
                    $owner->tenants()->attach($tenant->id);
                }

                // 3. Update central tenant_user record
                $tenantUserCentral = $owner->tenantUsers()->where('tenant_id', $tenant->id)->first();
                if ($tenantUserCentral) {
                    $tenantUserCentral->update([
                        'google_id' => $owner->google_id,
                        'nusawork_id' => $owner->nusawork_id,
                        'avatar' => $owner->avatar,
                        'role' => 'super_admin',
                        'tenant_join_date' => is_null($tenantUserCentral->tenant_join_date) ? now() : $tenantUserCentral->tenant_join_date,
                        'created_at' => is_null($tenantUserCentral->created_at) ? now() : $tenantUserCentral->created_at,
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }
}
