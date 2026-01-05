<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_certificates', function (Blueprint $table) {
            $table->string('passphrase_hash', 64)->after('private_key_path')->nullable()->comment('SHA256 hash used to encrypt private key');
        });
    }

    public function down(): void
    {
        Schema::table('user_certificates', function (Blueprint $table) {
            $table->dropColumn('passphrase_hash');
        });
    }
};
