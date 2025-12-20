<?php

namespace App\Helpers;

/**
 * Helper untuk generate dan validate file token dengan HMAC-SHA256
 * Menggantikan MD5 yang weak untuk keamanan lebih baik (Sonar S4790)
 */
class FileTokenHelper
{
    /**
     * Generate HMAC-SHA256 hash untuk file token
     * 
     * @param int $applicationId
     * @param string $path
     * @param string $fileType
     * @return string
     */
    public static function generateHash(int $applicationId, string $path, string $fileType = 'cv'): string
    {
        $secretKey = config('app.key');
        $data = $applicationId . '|' . $path . '|' . $fileType;
        
        return hash_hmac('sha256', $data, $secretKey);
    }

    /**
     * Validasi HMAC-SHA256 hash dengan timing-safe comparison
     * 
     * @param string $providedHash
     * @param int $applicationId
     * @param string $path
     * @param string $fileType
     * @return bool
     */
    public static function validateHash(string $providedHash, int $applicationId, string $path, string $fileType = 'cv'): bool
    {
        $expectedHash = self::generateHash($applicationId, $path, $fileType);
        
        // Gunakan hash_equals untuk timing-safe comparison (mencegah timing attack)
        return hash_equals($expectedHash, $providedHash);
    }
}
