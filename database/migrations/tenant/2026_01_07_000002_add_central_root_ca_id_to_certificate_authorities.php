<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_authorities', function (Blueprint $table) {
            $table->unsignedBigInteger('central_root_ca_id')->nullable()->after('id');
            $table->boolean('is_central')->default(false)->after('is_revoked');
        });
    }

    public function down(): void
    {
        Schema::table('certificate_authorities', function (Blueprint $table) {
            $table->dropColumn(['central_root_ca_id', 'is_central']);
        });
    }
};
