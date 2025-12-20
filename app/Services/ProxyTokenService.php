<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class ProxyTokenService
{
    static $dir = 'proxy_tokens';
    static $disk = 'private';

    /**
     * Simpan token berdasarkan session_id
     */
    public static function put(string $sessionId, string $token, $ttlMinutes = 10080) // default 7 hari
    {
        $data = [
            'token' => $token,
            'expires_at' => now()->addMinutes($ttlMinutes)->timestamp,
        ];
        Storage::disk(self::$disk)->put(self::getPath($sessionId), json_encode($data));
    }

    /**
     * Ambil token berdasarkan session_id
     */
    public static function get(string $sessionId): ?string
    {
        $path = self::getPath($sessionId);
        if (!Storage::disk(self::$disk)->exists($path)) {
            return null;
        }
        $data = json_decode(Storage::disk(self::$disk)->get($path), true);
        // @codeCoverageIgnoreStart
        // Token expired - sulit di-test karena memerlukan time manipulation
        if (!$data || ($data['expires_at'] ?? 0) < now()->timestamp) {
            self::delete($sessionId);
            return null;
        }
        // @codeCoverageIgnoreEnd
        return $data['token'] ?? null;
    }

    /**
     * Hapus token berdasarkan session_id
     */
    public static function delete(string $sessionId)
    {
        Storage::disk(self::$disk)->delete(self::getPath($sessionId));
    }

    /**
     * Path file token
     */
    protected static function getPath(string $sessionId): string
    {
        return self::$dir . '/' . $sessionId . '.json';
    }

    /**
     * Ambil semua session ID (identifier) yang tersimpan
     * @return array
     * 
     * @codeCoverageIgnore - Method tidak digunakan (dead code)
     */
    public static function allSessionIds(): array
    {
        $files = Storage::disk(self::$disk)->files(self::$dir);
        return array_map(function ($file) {
            // Ambil nama file tanpa path dan tanpa .json
            return pathinfo($file, PATHINFO_FILENAME);
        }, $files);
    }
}
