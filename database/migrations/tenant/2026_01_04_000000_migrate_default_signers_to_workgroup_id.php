<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if workgroups table exists, if not create it
        if (!Schema::hasTable('workgroups')) {
            Schema::create('workgroups', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Check if default_signers table exists
        if (Schema::hasTable('default_signers')) {
            // Check if workgroup_id column already exists
            if (!Schema::hasColumn('default_signers', 'workgroup_id')) {
                // Get distinct workgroup values from existing data
                $workgroups = DB::table('default_signers')
                    ->distinct()
                    ->pluck('workgroup')
                    ->toArray();

                // Create workgroup records for each distinct value
                foreach ($workgroups as $workgroupName) {
                    if ($workgroupName) {
                        DB::table('workgroups')->insertOrIgnore([
                            'id' => \Illuminate\Support\Str::ulid(),
                            'name' => $workgroupName,
                            'description' => 'Migrated from default_signers',
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Add workgroup_id column
                Schema::table('default_signers', function (Blueprint $table) {
                    $table->foreignUlid('workgroup_id')
                        ->nullable()
                        ->constrained('workgroups')
                        ->onDelete('cascade');
                });

                // Migrate data from workgroup (string) to workgroup_id (ULID)
                DB::statement('
                    UPDATE default_signers ds
                    SET workgroup_id = w.id
                    FROM workgroups w
                    WHERE ds.workgroup = w.name
                ');

                // Make workgroup_id NOT NULL after migration
                Schema::table('default_signers', function (Blueprint $table) {
                    $table->dropColumn('workgroup');
                });

                // Update index
                Schema::table('default_signers', function (Blueprint $table) {
                    $table->index(['workgroup_id', 'step_order']);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('default_signers')) {
            Schema::table('default_signers', function (Blueprint $table) {
                $table->dropForeignKeyConstraints();
                $table->dropColumn('workgroup_id');
                $table->string('workgroup')->nullable();
            });
        }

        if (Schema::hasTable('workgroups')) {
            Schema::dropIfExists('workgroups');
        }
    }
};
