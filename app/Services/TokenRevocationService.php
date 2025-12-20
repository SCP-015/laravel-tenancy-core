<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Service class for handling the revocation of OAuth access tokens.
 */
class TokenRevocationService
{
    /**
     * Revokes all currently active access tokens for all users.
     * This forces every user to log in again.
     *
     * @return int The number of tokens revoked.
     */
    public function revokeAllActiveTokens(): int
    {
        return DB::table('oauth_access_tokens')
            ->where('revoked', false)
            ->update(['revoked' => true]);
    }

    /**
     * Revokes all active access tokens for a specific list of user IDs.
     *
     * @param array $userIds An array of user IDs whose tokens should be revoked.
     * @return int The number of tokens revoked.
     */
    public function revokeTokensForUsers(array $userIds): int
    {
        if (empty($userIds)) {
            return 0;
        }

        return DB::table('oauth_access_tokens')
            ->whereIn('user_id', $userIds)
            ->where('revoked', false)
            ->update(['revoked' => true]);
    }
}
