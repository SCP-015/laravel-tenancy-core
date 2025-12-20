<?php

namespace App\Models\Passport;

use Laravel\Passport\Token as PassportToken;

class Token extends PassportToken
{
    protected $connection = 'pgsql'; // Ganti sesuai nama koneksi central jika berbeda
}
