<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;
use App\Models\TenantSlugHistory;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Illuminate\Http\Request;
use Stancl\Tenancy\Contracts\TenantCouldNotBeIdentifiedException;

class InitializeTenancyByPathOrId extends InitializeTenancyByPath
{
    public function handle(Request $request, Closure $next)
    {
        $response = null;
        try {
            $route = $request->route();

            // Cek apakah route memiliki parameter tenant
            $tenantIdentifier = $route?->parameter('tenant') ?? $request->segment(1);

            /** @var Tenant|null $tenant */
            $tenant = null;
            if (is_string($tenantIdentifier) && $tenantIdentifier !== '') {
                // Jika yang diberikan adalah ID tenant
                $tenant = Tenant::find($tenantIdentifier);

                // Jika bukan ID, coba cari berdasarkan slug
                if (!$tenant) {
                    $tenant = Tenant::where('slug', $tenantIdentifier)->first();
                }
            }
            $isHistoricalSlug = false;

            if (!$tenant) {
                $history = TenantSlugHistory::where('slug', $tenantIdentifier)->first();
                if ($history) {
                    $candidateTenant = Tenant::find($history->tenant_id);
                    $isHistoryRedirectEnabled = (bool) ($candidateTenant?->enable_slug_history_redirect ?? false);

                    if ($isHistoryRedirectEnabled) {
                        $tenant = $candidateTenant;
                        $isHistoricalSlug = $tenant !== null && $tenant->slug !== $tenantIdentifier;
                    }
                }
            }

            // @codeCoverageIgnoreStart
            // Edge case: tenant tidak ditemukan di web route (hanya terjadi di web context)
            if (!$tenant && in_array('web', $route->gatherMiddleware())) {
                abort(404);
            }
            // @codeCoverageIgnoreEnd

            // Cari tenant berdasarkan slug
            if ($tenant) {
                // Ganti parameter tenant dengan ID tenant
                if ($route) {
                    $route->setParameter('tenant', $tenant->id);
                }

                // Inisialisasi tenancy secara langsung.
                // Parent middleware InitializeTenancyByPath mengidentifikasi tenant berdasarkan URL segment,
                // sehingga pada kasus parameter {tenant} berupa slug, tenancy bisa gagal diinisialisasi.
                tenancy()->initialize($tenant);
            }

            if (
                $tenant &&
                $isHistoricalSlug &&
                in_array('web', $route->gatherMiddleware()) &&
                in_array($request->getMethod(), ['GET', 'HEAD'], true)
            ) {
                $segments = $request->segments();
                if (!empty($segments)) {
                    $segments[0] = $tenant->slug;
                    $targetPath = '/' . implode('/', $segments);
                    $queryString = $request->getQueryString();
                    if (!empty($queryString)) {
                        $targetPath .= '?' . $queryString;
                    }

                    return redirect()->to($targetPath, 302)
                        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                        ->header('Pragma', 'no-cache');
                }
            }

            if ($tenant) {
                $response = $next($request);
            } else {
                $response = parent::handle($request, $next);
            }
        // @codeCoverageIgnoreStart
        // Exception handling untuk edge cases (TenantNotFound, CentralDomain access)
        } catch (\Throwable $th) {
            // Handle TenantNotFound
            if ($th instanceof TenantCouldNotBeIdentifiedException) {
                // Handle PreventAccessFromCentralDomains
                if (in_array($request->getHost(), config('tenancy.central_domains'))) {
                    $abortRequest = static::$abortRequest ?? function () {
                        abort(404);
                    };

                    $response = $abortRequest($request, $next);
                } else {
                    $response = $next($request);
                }
            } else {
                throw $th;
            }
        }
        // @codeCoverageIgnoreEnd

        return $response;
    }
}
