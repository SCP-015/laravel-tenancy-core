<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tenants', 'slug_changed_at')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->timestamp('slug_changed_at')->nullable()->after('slug');
            });
        }

        if (! Schema::hasTable('tenant_slug_histories')) {
            Schema::create('tenant_slug_histories', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id');
                $table->string('slug');
                $table->timestamps();

                $table->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');

                $table->unique('slug');
                $table->index('tenant_id');
            });
        }

        if (Schema::hasTable('tenant_users') && Schema::hasColumn('tenant_users', 'is_owner')) {
            DB::table('tenants')
                ->whereNotNull('owner_id')
                ->orderBy('id')
                ->chunk(200, function ($tenants) {
                    $ownerIds = collect($tenants)->pluck('owner_id')->filter()->unique()->values()->all();
                    $owners = DB::table('users')
                        ->whereIn('id', $ownerIds)
                        ->get(['id', 'global_id']);
                    $ownerGlobalIdById = $owners
                        ->filter(fn ($u) => !empty($u->global_id))
                        ->mapWithKeys(fn ($u) => [$u->id => $u->global_id]);

                    foreach ($tenants as $tenant) {
                        $globalUserId = $ownerGlobalIdById[$tenant->owner_id] ?? null;
                        if (!$globalUserId) {
                            continue;
                        }

                        $exists = DB::table('tenant_users')
                            ->where('tenant_id', $tenant->id)
                            ->where('global_user_id', $globalUserId)
                            ->exists();

                        if ($exists) {
                            DB::table('tenant_users')
                                ->where('tenant_id', $tenant->id)
                                ->where('global_user_id', $globalUserId)
                                ->update(['is_owner' => true]);
                        } else {
                            DB::table('tenant_users')->insert([
                                'tenant_id' => $tenant->id,
                                'global_user_id' => $globalUserId,
                                'role' => 'super_admin',
                                'is_owner' => true,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tenant_slug_histories')) {
            Schema::drop('tenant_slug_histories');
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'slug_changed_at')) {
                $table->dropColumn('slug_changed_at');
            }
        });
    }
};
