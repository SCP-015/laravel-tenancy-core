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
        Schema::create('default_signers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workgroup_id')->constrained('workgroups')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->comment('User yang menjadi default signer');
            $table->integer('step_order')->default(1)->comment('Urutan signing untuk mode sequential');
            $table->string('role')->nullable()->comment('Role/jabatan signer, e.g. Manager, Director');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['workgroup_id', 'step_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('default_signers');
    }
};
