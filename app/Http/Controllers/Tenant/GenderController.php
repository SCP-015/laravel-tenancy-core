<?php

namespace App\Http\Controllers\Tenant;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Tenant\GenderResource;
use App\Services\Tenant\GenderService;

class GenderController extends Controller
{
    protected $service;

    public function __construct(GenderService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the genders.
     */
    public function index()
    {
        $data = GenderResource::collection($this->service->all());

        return ApiResponse::success($data, __('Experience levels retrieved successfully'));
    }
}
