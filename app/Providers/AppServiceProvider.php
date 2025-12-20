<?php

namespace App\Providers;

use App\Auth\CustomAccessToken;
use Dedoc\Scramble\Scramble;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Passport::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        Passport::useTokenModel(\App\Models\Passport\Token::class);
        Passport::useRefreshTokenModel(\App\Models\Passport\RefreshToken::class);
        Passport::useAuthCodeModel(\App\Models\Passport\AuthCode::class);
        Passport::useClientModel(\App\Models\Passport\Client::class);
        Passport::usePersonalAccessClientModel(\App\Models\Passport\PersonalAccessClient::class);

        // Load keys from storage menggunakan API Keys yang sama dengan central
        Passport::loadKeysFrom(storage_path(''));

        // custom access token
        Passport::useAccessTokenEntity(CustomAccessToken::class);

        // Register DOCS API untuk central dan tenant
        Scramble::registerApi('central', ['api_path' => 'api', 'servers' => ['Central' => 'api']]);
        Scramble::registerApi('tenant', ['api_path' => '{tenant}/api', 'servers' => ['Tenant' => '{tenant}/api']]);
    }
}
