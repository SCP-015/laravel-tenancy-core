<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\LocationIndonesiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationIndonesiaController extends Controller
{
    /**
     * Display a listing of all provinces.
     */
    public function indexProvinces(): JsonResponse
    {
        $provinces = LocationIndonesiaService::getAllProvinces();

        return response()->json($provinces);
    }

    /**
     * Display the specified province by code.
     */
    public function showProvince(Request $request): JsonResponse
    {
        $provinceCode = $request->route('provinceCode');
        $province = LocationIndonesiaService::findProvinceByCode($provinceCode);
        if (! $province) {
            return response()->json(['message' => __('Province not found.')], 404);
        }

        return response()->json($province);
    }

    /**
     * Display a listing of cities for a given province code.
     */
    public function indexCities(Request $request): JsonResponse
    {
        $provinceCode = $request->route('provinceCode');
        $province = LocationIndonesiaService::findProvinceByCode($provinceCode);
        if (! $province) {
            return response()->json(['message' => __('Province not found for the given code.')], 404);
        }
        $cities = LocationIndonesiaService::getCitiesByProvinceCode($provinceCode);

        return response()->json($cities);
    }

    /**
     * Display the specified city by code.
     */
    public function showCity(Request $request): JsonResponse
    {
        $cityCode = $request->route('cityCode');
        $city = LocationIndonesiaService::findCityByCode($cityCode);
        if (! $city) {
            return response()->json(['message' => __('City not found.')], 404);
        }

        return response()->json($city);
    }

    /**
     * Display a listing of districts for a given city code.
     */
    public function indexDistricts(Request $request): JsonResponse
    {
        $cityCode = $request->route('cityCode');
        $city = LocationIndonesiaService::findCityByCode($cityCode);
        if (! $city) {
            return response()->json(['message' => __('City not found for the given code.')], 404);
        }
        $districts = LocationIndonesiaService::getDistrictsByCityCode($cityCode);

        return response()->json($districts);
    }

    /**
     * Display the specified district by code.
     */
    public function showDistrict(Request $request): JsonResponse
    {
        $districtCode = $request->route('districtCode');
        $district = LocationIndonesiaService::findDistrictByCode($districtCode);
        if (! $district) {
            return response()->json(['message' => __('District not found.')], 404);
        }

        return response()->json($district);
    }

    /**
     * Display a listing of villages for a given district code.
     */
    public function indexVillages(Request $request): JsonResponse
    {
        $districtCode = $request->route('districtCode');
        $district = LocationIndonesiaService::findDistrictByCode($districtCode);
        if (! $district) {
            return response()->json(['message' => __('District not found for the given code.')], 404);
        }
        $villages = LocationIndonesiaService::getVillagesByDistrictCode($districtCode);

        return response()->json($villages);
    }

    /**
     * Display the specified village by code.
     */
    public function showVillage(Request $request): JsonResponse
    {
        $villageCode = $request->route('villageCode');
        $village = LocationIndonesiaService::findVillageByCode($villageCode);
        if (! $village) {
            return response()->json(['message' => __('Village not found.')], 404);
        }

        return response()->json($village);
    }
}
