<?php

namespace App\Services\Tenant;

use App\Traits\Loggable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Exception;

class ListFeedbackService
{
    use Loggable;
    
    /**
     * Waktu cache dalam menit
     */
    private const CACHE_DURATION = 10;

    /**
     * Mengambil feedback pribadi dari Google Sheets dengan cache.
     *
     * @param bool $forceRefresh Jika true, akan mengabaikan cache dan mengambil data baru
     * @return array
     */
    public function getPersonalFeedback($forceRefresh = false)
    {
        try {
            $user = Auth::user();
            $tenant = tenant();
            
            // Buat cache key berdasarkan user dan tenant
            $cacheKey = "feedback_data_{$user->id}_{$tenant->id}";
            
            // Jika tidak diminta refresh dan cache ada, gunakan data dari cache
            if (!$forceRefresh && Cache::has($cacheKey)) {
                $cachedData = Cache::get($cacheKey);
                
                // Tambahkan informasi cache ke response
                if (is_array($cachedData) && !isset($cachedData['cache_info'])) {
                    $cachedData['cache_info'] = [
                        'from_cache' => true,
                        'cached_at' => Cache::get("{$cacheKey}_timestamp"),
                        'expires_in' => self::CACHE_DURATION * 60 - (time() - Cache::get("{$cacheKey}_timestamp"))
                    ];
                }
                
                return $cachedData;
            }

            // URL API Google Sheets
            $apiUrl = 'https://script.google.com/macros/s/AKfycbzLCRin0wt-BKzKhL7Kt5on-aBH-IGxyDX-z2RcfZ8q-fYukfDaDP4wHQx8k0DFJtrO/exec';
            
            // Menggunakan Laravel HTTP Client untuk mengirim permintaan GET
            $response = Http::get($apiUrl, [
                'q' => $user->email,
                'tenant_name' => $tenant->name
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Simpan data ke cache
                Cache::put($cacheKey, $responseData, now()->addMinutes(self::CACHE_DURATION));
                Cache::put("{$cacheKey}_timestamp", time(), now()->addMinutes(self::CACHE_DURATION));
                
                // Tambahkan informasi cache ke response
                if (is_array($responseData) && !isset($responseData['cache_info'])) {
                    $responseData['cache_info'] = [
                        'from_cache' => false,
                        'cached_at' => time(),
                        'expires_in' => self::CACHE_DURATION * 60
                    ];
                }
                
                return $responseData;
            }

            $this->logError('Google Sheets API responded with an error.', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return [
                'status' => 'error',
                'message' => 'Gagal mengambil feedback dari Google Sheets.'
            ];

        } catch (Exception $e) {
            $this->logError('Error fetching data from Google Sheets: ' . $e->getMessage(), [
                'user_email' => Auth::check() ? Auth::user()->email : 'guest',
                'tenant_id' => tenant('id')
            ]);
            return [
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil feedback.'
            ];
        }
    }
}
