<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Referencing users table (tenant scope or central user synced)
            $table->foreignId('certificate_authority_id')->constrained('certificate_authorities')->cascadeOnDelete();
            $table->string('serial_number')->unique();
            $table->string('common_name'); // Usually User Name
            $table->string('email');
            $table->timestamp('valid_from');
            $table->timestamp('valid_to');
            $table->string('certificate_path');
            $table->string('private_key_path'); // Encrypted
            $table->string('passphrase')->nullable(); // Hashed passphrase for key protection
            $table->boolean('is_revoked')->default(false);
            $table->timestamps();
            
            // Optional: Index for search
            $table->index(['user_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_certificates');
    }
};
