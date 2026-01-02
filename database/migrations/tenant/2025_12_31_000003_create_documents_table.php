<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Uploader
            $table->string('title');
            $table->string('filename');
            $table->string('original_file_path');
            $table->string('signed_file_path')->nullable();
            $table->string('original_hash'); // SHA-256 of original file
            $table->string('current_hash')->nullable(); // SHA-256 of signed file
            $table->enum('status', ['draft', 'pending', 'signed', 'verified', 'rejected'])->default('draft');
            $table->json('metadata')->nullable(); // Store extra info like page count, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
