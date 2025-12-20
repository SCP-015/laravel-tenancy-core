<?php

namespace App\Listeners;

use App\Jobs\SyncNusaworkMasterData;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\TenantUserAuditService;
use App\Traits\Loggable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Stancl\Tenancy\Events\DatabaseMigrated;
use Stancl\Tenancy\Events\TenancyInitialized;

/**
 * Event Listener: SyncOAuthDataToTenant
 * 
 * This listener is excluded from code coverage because:
 * - Event listeners are side effects triggered by events
 * - Requires complex multi-tenant database setup
 * - Involves coordination between central and tenant databases
 * - Better tested through integration/E2E tests
 * 
 * @codeCoverageIgnore
 */
class SyncOAuthDataToTenant
{
    use Loggable;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TenancyInitialized $event)
    {
        $auth = Auth::user();
        if ($auth) {
            $tenant = Tenant::where('owner_id', $auth->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $this->logDebug('Central User: ', ['data' => $auth, 'tenant' => $tenant]);

            // Subscribe ke event DatabaseMigrated
            Event::listen(DatabaseMigrated::class, function () use ($auth, $tenant) {
                $connection = config('database.default');

                $this->logInfo('DB Connection: ' . $connection);

                // Sync user ke tenant dengan data yang tersedia
                $user = $auth;

                try {
                    // Attach relation jika belum ada (dengan pengecekan)
                    if (!$user->tenants()->where('tenant_id', $tenant->id)->exists()) {
                        $user->tenants()->attach($tenant->id);
                    }
                } catch (\Exception $e) {
                    $this->logError('Failed to attach user to tenant', [
                        'user_id' => $user->id,
                        'tenant_id' => $tenant->id,
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    // Continue dengan proses lainnya meskipun attach gagal
                }

                $this->logInfo('Info has been synced to tenant', ['data' => $user->tenants()->get()]);

                // Buat atau update tenant_user record dengan data yang ada
                $tenantUserCentral = $user->tenantUsers()->where('tenant_id', $tenant->id)->first();
                if ($tenantUserCentral) {
                    $tenantUserCentral->update([
                        'google_id' => $user->google_id,
                        'nusawork_id' => $user->nusawork_id,
                        'avatar' => $user->avatar,
                        'role' => 'super_admin',
                        'is_owner' => true,
                        'tenant_join_date' => is_null($tenantUserCentral->tenant_join_date) ? now() : $tenantUserCentral->tenant_join_date,
                        'created_at' => is_null($tenantUserCentral->created_at) ? now() : $tenantUserCentral->created_at,
                        'updated_at' => now(),
                    ]);
                }

                $this->logInfo('Sync user to tenant_users successful', ['data' => $tenantUserCentral]);

                try {
                    $this->logInfo('Checking central database connection');

                    DB::connection($connection)->getPdo();

                    $this->logInfo('Central database connection successful');
                } catch (\Exception $e) {
                    $this->logError('Failed to connect to central database', [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    throw $e;
                }

                // Sinkronisasi tabel Oauth dari central ke tenant
                $this->logInfo('Fetching OAuth data from central database');

                $oauthClients = DB::connection($connection)->table('oauth_clients')->get();
                $oauthPACs = DB::connection($connection)->table('oauth_personal_access_clients')->get();
                $oauthAccessTokens = DB::connection($connection)->table('oauth_access_tokens')->get();

                $this->logInfo('OAuth data fetched', [
                    'clients_count' => $oauthClients->count(),
                    'pacs_count' => $oauthPACs->count(),
                    'tokens_count' => $oauthAccessTokens->count(),
                ]);

                // Sinkronisasi data OAuth ke tenant
                $tenant->run(function () use ($oauthClients, $oauthPACs, $oauthAccessTokens, $user, $tenant) {
                    try {
                        foreach ($oauthClients as $oauthClient) {
                            $result = DB::table('oauth_clients')->insertOrIgnore((array) $oauthClient);
                            if (! $result) {
                                $this->logWarning('Failed to insert OAuth client', ['client_id' => $oauthClient->id]);
                            }
                        }

                        foreach ($oauthPACs as $oauthPAC) {
                            $result = DB::table('oauth_personal_access_clients')->insertOrIgnore((array) $oauthPAC);
                            if (! $result) {
                                $this->logWarning('Failed to insert OAuth PAC', ['client_id' => $oauthPAC->client_id]);
                            }
                        }

                        foreach ($oauthAccessTokens as $OAT) {
                            $result = DB::table('oauth_access_tokens')->insertOrIgnore((array) $OAT);
                            if (! $result) {
                                $this->logWarning('Failed to insert OAuth access token', ['token_id' => $OAT->id]);
                            }
                        }

                        $this->logInfo('OAuth data synced successfully to tenant');
                    } catch (\Exception $e) {
                        $this->logError('Failed to sync OAuth data to tenant', [
                            'message' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                });

                // Sync user ke tenant context
                $tenant->run(function () use ($user, $tenant) {
                    // Sync user ke tenant context
                    $tenantUser = \App\Models\Tenant\User::where('global_id', $user->global_id)->first();
                    
                    if ($tenantUser) {
                        // Disable auditing untuk update ini
                        $tenantUser->disableAuditing();
                        
                        // Update existing tenant user
                        $tenantUser->update([
                            'tenant_id' => $tenant->id,
                            'email_verified_at' => $user->email_verified_at,
                            'google_id' => $user->google_id,
                            'nusawork_id' => $user->nusawork_id,
                            'avatar' => $user->avatar,
                            'role' => 'super_admin', // Owner tenant
                            'is_owner' => true,
                            'tenant_join_date' => is_null($tenantUser->tenant_join_date) ? now() : $tenantUser->tenant_join_date,
                            'last_login_ip' => $user->last_login_ip,
                            'last_login_at' => $user->last_login_at,
                            'last_login_user_agent' => $user->last_login_user_agent,
                            'created_at' => is_null($tenantUser->created_at) ? now() : $tenantUser->created_at,
                            'updated_at' => now(),
                        ]);
                        
                        $tenantUser->enableAuditing();
                        $tenantUser->syncRoles([$tenantUser->role]);

                        $this->logInfo('Sync user to tenant->users successful - updated existing user');
                    } else {
                        // Disable auditing untuk create ini
                        \App\Models\Tenant\User::disableAuditing();
                        
                        // Create new tenant user if not exists
                        $tenantUser = \App\Models\Tenant\User::create([
                            'global_id' => $user->global_id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'password' => $user->password,
                            'tenant_id' => $tenant->id,
                            'email_verified_at' => $user->email_verified_at,
                            'google_id' => $user->google_id,
                            'nusawork_id' => $user->nusawork_id,
                            'avatar' => $user->avatar,
                            'role' => 'super_admin', // Owner tenant
                            'is_owner' => true,
                            'tenant_join_date' => now(),
                            'last_login_ip' => $user->last_login_ip,
                            'last_login_at' => $user->last_login_at,
                            'last_login_user_agent' => $user->last_login_user_agent,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        
                        \App\Models\Tenant\User::enableAuditing();
                        $tenantUser->syncRoles([$tenantUser->role]);

                        $this->logInfo('Sync user to tenant->users successful - created new user');
                    }
                    
                    // Create manual audit log dengan event 'login' untuk owner yang baru create portal
                    $this->createLoginAuditLog($tenantUser, $user);
                });

                // Dispatch job untuk sinkronisasi master data Nusawork
                $this->logInfo('Dispatching SyncNusaworkMasterData job', [
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                ]);

                SyncNusaworkMasterData::dispatch($user, $tenant->id);
            });
        }
    }

    /**
     * Create manual audit log untuk event login
     *
     * @param  \App\Models\Tenant\User  $tenantUser
     * @param  \App\Models\User  $actorUser  Central user yang login
     * @return void
     */
    private function createLoginAuditLog($tenantUser, $actorUser): void
    {
        // Format last_login_at safely (bisa string atau Carbon object)
        $lastLoginAt = $tenantUser->last_login_at;
        if ($lastLoginAt instanceof \Carbon\Carbon) {
            $lastLoginAt = $lastLoginAt->format('Y-m-d H:i:s');
        }
        
        // Prepare new_values untuk audit log - selalu sertakan detail user
        $newValues = [
            'role' => $tenantUser->role,
            'name' => $tenantUser->name,
            'email' => $tenantUser->email,
            'last_login_ip' => $tenantUser->last_login_ip,
            'last_login_at' => $lastLoginAt,
        ];

        // Create audit log manual dengan event 'login'
        TenantUserAuditService::logLogin($tenantUser, $newValues, request(), $actorUser);
    }
}
