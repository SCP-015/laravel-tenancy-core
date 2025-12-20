<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\UniqueIdentifierGenerator;

/**
 * Generator ID unik untuk resource tenancy (khususnya Tenant).
 *
 * Menggunakan ULID karena:
 * - Lebih aman dari collision dibanding uniqid(), terutama saat proses paralel/worker tinggi.
 * - Format string relatif ringkas dan aman digunakan sebagai bagian dari nama database/path.
 *
 * Catatan:
 * - ULID bersifat sortable (mengandung komponen waktu). Untuk kebutuhan ID yang benar-benar acak,
 *   pertimbangkan mengganti ke UUID (misal: Str::uuid()).
 */
class UIDGenerator implements UniqueIdentifierGenerator
{
    public static function generate($resource): string
    {
        return (string) Str::ulid();
    }
}
