<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('company_category_id')
                ->nullable()
                ->after('plan') // Sesuaikan posisi kolom jika perlu
                ->constrained('company_categories')
                ->onDelete('set null'); // Jika kategori dihapus, tenant tidak ikut terhapus
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['company_category_id']);
            $table->dropColumn('company_category_id');
        });
    }
};
