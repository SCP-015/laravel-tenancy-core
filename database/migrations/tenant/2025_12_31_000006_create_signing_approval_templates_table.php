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
        Schema::create('signing_approval_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('signing_approval_steps', function (Blueprint $table) {
            $table->id();
            $table->uuid('template_id');
            $table->foreignId('user_id')->constrained('users');
            $table->string('role')->default('Signer');
            $table->integer('step_order');
            $table->timestamps();
            
            $table->foreign('template_id')->references('id')->on('signing_approval_templates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signing_approval_steps');
        Schema::dropIfExists('signing_approval_templates');
    }
};
