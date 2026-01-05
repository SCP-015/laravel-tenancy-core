<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update all existing certificates with the old hardcoded passphrase hash
        // This ensures backward compatibility for certificates created before this change
        DB::table('user_certificates')
            ->whereNull('passphrase_hash')
            ->update([
                'passphrase_hash' => '48124d404081da40b791ee3617307062211913346b9f2c3d59664687d7f78c89'
            ]);
    }

    public function down(): void
    {
        // Reset passphrase_hash for certificates that had the old hash
        DB::table('user_certificates')
            ->where('passphrase_hash', '48124d404081da40b791ee3617307062211913346b9f2c3d59664687d7f78c89')
            ->update(['passphrase_hash' => null]);
    }
};
