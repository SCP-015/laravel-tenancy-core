<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('education_levels', function (Blueprint $table): void {
            // Izinkan beberapa Education Level Nusahire menggunakan Nusawork Education yang sama
            // dengan menghapus unique constraint pada kolom nusawork_id
            $table->dropUnique('education_levels_nusawork_id_unique');

            // Tambahkan index biasa untuk menjaga performa query berdasarkan nusawork_id
            $table->index('nusawork_id', 'education_levels_nusawork_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('education_levels', function (Blueprint $table): void {
            // Kembalikan ke constraint sebelumnya: nusawork_id unik per education level
            $table->dropIndex('education_levels_nusawork_id_index');
            $table->unique('nusawork_id', 'education_levels_nusawork_id_unique');
        });
    }
};
