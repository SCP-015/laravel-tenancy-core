<?php

namespace App\Services;

use App\Models\Indonesia\City;
use App\Models\Indonesia\District;
use App\Models\Indonesia\Province;
use App\Models\Indonesia\Village;
use Illuminate\Database\Eloquent\Collection;

class LocationIndonesiaService
{
    public static function getAllProvinces(): Collection
    {
        return Province::orderBy('name')->get();
    }

    /**
     * @codeCoverageIgnore - Method tidak digunakan, hanya findByCode() yang digunakan di controller
     */
    public static function findProvinceById(int $id): ?Province
    {
        return Province::find($id);
    }

    public static function findProvinceByCode(string $code): ?Province
    {
        return Province::where('code', $code)->first();
    }

    public static function getCitiesByProvinceCode(string $provinceCode): Collection
    {
        return City::where('province_code', $provinceCode)->orderBy('name')->get(); //
    }

    /**
     * @codeCoverageIgnore - Method tidak digunakan, hanya findByCode() yang digunakan di controller
     */
    public static function findCityById(int $id): ?City
    {
        return City::find($id);
    }

    public static function findCityByCode(string $code): ?City
    {
        return City::where('code', $code)->first();
    }

    public static function getDistrictsByCityCode(string $cityCode): Collection
    {
        return District::where('city_code', $cityCode)->orderBy('name')->get(); //
    }

    /**
     * @codeCoverageIgnore - Method tidak digunakan, hanya findByCode() yang digunakan di controller
     */
    public static function findDistrictById(int $id): ?District
    {
        return District::find($id);
    }

    public static function findDistrictByCode(string $code): ?District
    {
        return District::where('code', $code)->first();
    }

    public static function getVillagesByDistrictCode(string $districtCode): Collection
    {
        return Village::where('district_code', $districtCode)->orderBy('name')->get(); //
    }

    /**
     * @codeCoverageIgnore - Method tidak digunakan, hanya findByCode() yang digunakan di controller
     */
    public static function findVillageById(int $id): ?Village
    {
        return Village::find($id);
    }

    public static function findVillageByCode(string $code): ?Village
    {
        return Village::where('code', $code)->first();
    }
}
