<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Panggil command tenants:fix-user-roles untuk sinkronisasi role
        Artisan::call('tenants:fix-user-roles');
        
        // Output pesan hasil eksekusi command
        $output = Artisan::output();
        if (app()->runningInConsole()) {
            echo $output;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu melakukan apa-apa saat rollback
        // Karena kita tidak ingin menghapus role yang sudah ada
    }
};
