<?php

use Illuminate\Database\Migrations\Migration;
use App\Services\TokenRevocationService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tokenService = new TokenRevocationService();
        $tokenService->revokeAllActiveTokens();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
