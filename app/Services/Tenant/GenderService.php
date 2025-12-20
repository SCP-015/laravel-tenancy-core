<?php

namespace App\Services\Tenant;

use App\Models\Tenant\ExperienceLevel;
use App\Models\Tenant\Gender;

class GenderService
{
    public function all()
    {
        return Gender::all();
    }
}
