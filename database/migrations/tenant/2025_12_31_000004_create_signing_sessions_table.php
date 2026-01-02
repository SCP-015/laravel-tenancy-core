<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signing_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('title');
            $table->enum('mode', ['sequential', 'parallel', 'hybrid'])->default('parallel');
            $table->enum('status', ['in_progress', 'completed', 'cancelled'])->default('in_progress');
            $table->unsignedBigInteger('created_by'); // User ID
            $table->integer('current_step_order')->default(1); // For sequential mode
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signing_sessions');
    }
};
