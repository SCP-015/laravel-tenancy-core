<?php

namespace App\Http\Controllers\Tenant;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreEducationLevelRequest;
use App\Http\Requests\Tenant\UpdateEducationLevelRequest;
use App\Http\Resources\Tenant\EducationLevelResource;
use App\Models\Tenant\EducationLevel;
use App\Services\Tenant\EducationLevelService;
use App\Traits\HasPermissionTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EducationLevelController extends Controller
{
    use HasPermissionTrait;

    protected $service;

    public function __construct(EducationLevelService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the education levels.
     */
    public function index()
    {
        $this->checkPermission('education_levels.view');

        $data = EducationLevelResource::collection($this->service->all());

        return ApiResponse::success($data, __('Education levels retrieved successfully'));
    }

    /**
     * Store a newly created education level in storage.
     */
    public function store(StoreEducationLevelRequest $request)
    {
        $this->checkPermission('education_levels.create');

        $validated = $request->validated();

        $created = $this->service->create($validated);

        return ApiResponse::success(
            new EducationLevelResource($created),
            __('Education level created successfully'),
            201
        );
    }

    /**
     * Display the specified education level.
     */
    public function show(Request $request)
    {
        $this->checkPermission('education_levels.view');

        $educationLevel = EducationLevel::findOrFail($request->route('education_level'));

        return ApiResponse::success(
            new EducationLevelResource($educationLevel),
            __('Education level details retrieved')
        );
    }

    /**
     * Update the specified education level in storage.
     */
    public function update(UpdateEducationLevelRequest $request)
    {
        $this->checkPermission('education_levels.update');

        $educationLevel = EducationLevel::findOrFail($request->route('education_level'));

        $validated = $request->validated();

        $updated = $this->service->update($educationLevel, $validated);

        return ApiResponse::success(new EducationLevelResource($updated), __('Education level updated successfully'));
    }

    /**
     * Remove the specified education level from storage.
     */
    public function destroy(Request $request)
    {
        $this->checkPermission('education_levels.delete');

        $educationLevel = EducationLevel::findOrFail($request->route('education_level'));

        $this->service->delete($educationLevel);

        return ApiResponse::success(null, __('Education level deleted successfully'), 200);
    }

    /**
     * Display a listing of archived education levels.
     */
    public function archived()
    {
        $this->checkPermission('education_levels.view');

        $data = $this->service->archived();

        return ApiResponse::success(
            EducationLevelResource::collection($data),
            __('Archived education levels retrieved successfully')
        );
    }

    /**
     * Restore the specified archived education level.
     */
    public function restore(Request $request)
    {
        $this->checkPermission('education_levels.restore');

        $restored = $this->service->restore($request->route('educationLevelId'));

        return ApiResponse::success(new EducationLevelResource($restored), __('Education level restored successfully'));
    }

    /**
     * Permanently delete the specified education level from storage.
     */
    public function forceDelete(Request $request)
    {
        $this->checkPermission('education_levels.force_delete');

        $this->service->forceDelete($request->route('educationLevelId'));

        return ApiResponse::success(null, __('Education level permanently deleted'), 200);
    }
}
