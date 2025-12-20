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
        Schema::create('id_villages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('code', 10)->unique();
            $table->char('district_code', 7);
            $table->string('name', 255);
            $table->text('meta')->nullable();
            $table->timestamps();

            $table->foreign('district_code')
                ->references('code')
                ->on('id_districts')
                ->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('id_villages');
    }
};
