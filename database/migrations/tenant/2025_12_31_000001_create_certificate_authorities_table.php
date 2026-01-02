<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_authorities', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Organization Name (O)
            $table->string('common_name')->nullable(); // Common Name (CN)
            $table->string('serial_number')->unique();
            $table->timestamp('valid_from');
            $table->timestamp('valid_to');
            $table->string('certificate_path'); // Path to .crt
            $table->string('private_key_path'); // Path to .key (encrypted)
            $table->string('organization_unit')->nullable();
            $table->string('country')->default('ID');
            $table->string('email')->nullable();
            $table->boolean('is_revoked')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_authorities');
    }
};
