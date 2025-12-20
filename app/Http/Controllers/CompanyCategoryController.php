<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\SaveCompanyCategoryRequest;
use App\Http\Resources\CompanyCategoryResource;
use App\Models\CompanyCategory;
use App\Services\CompanyCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyCategoryController extends Controller
{
    protected CompanyCategoryService $service;

    public function __construct(CompanyCategoryService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return CompanyCategoryResource::collection($this->service->getAll());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SaveCompanyCategoryRequest $request)
    {
        $category = $this->service->create($request->validated());

        return ApiResponse::success(
            CompanyCategoryResource::make($category),
            __('Company category created successfully')
        );
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $companyCategory = CompanyCategory::findOrFail($id);
        return CompanyCategoryResource::make($companyCategory);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SaveCompanyCategoryRequest $request, $id)
    {
        $companyCategory = CompanyCategory::findOrFail($id);
        $category = $this->service->update($companyCategory, $request->validated());

        return ApiResponse::success(
            CompanyCategoryResource::make($category),
            __('Company category updated successfully')
        );
    }
}
