<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('signing_session_id')->constrained('signing_sessions')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->foreignId('certificate_id')->nullable()->constrained('user_certificates'); // Linked when signed
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            
            $table->string('role')->nullable(); // e.g. Manager, Witness
            $table->integer('step_order')->default(1); // Order in sequential workflow
            $table->boolean('is_required')->default(true);
            
            $table->enum('status', ['pending', 'signed', 'rejected'])->default('pending');
            $table->string('signature_file_path')->nullable(); // .sig file path
            $table->timestamp('signed_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamps();
            
            // Ensure unique signer per session? No, maybe same user signs multiple times (rare but possible)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
