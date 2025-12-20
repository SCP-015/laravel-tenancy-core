<?php

namespace App\Http\Controllers\Tenant;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreExperienceLevelRequest;
use App\Http\Requests\Tenant\UpdateExperienceLevelRequest;
use App\Http\Resources\Tenant\ExperienceLevelResource;
use App\Models\Tenant\ExperienceLevel;
use App\Services\Tenant\ExperienceLevelService;
use App\Traits\HasPermissionTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExperienceLevelController extends Controller
{
    use HasPermissionTrait;

    protected $service;

    public function __construct(ExperienceLevelService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the experience levels.
     */
    public function index()
    {
        $this->checkPermission('experience_levels.view');

        $data = ExperienceLevelResource::collection($this->service->all());

        return ApiResponse::success($data, __('Experience levels retrieved successfully'));
    }

    /**
     * Store a newly created experience level in storage.
     */
    public function store(StoreExperienceLevelRequest $request)
    {
        $this->checkPermission('experience_levels.create');

        $validated = $request->validated();

        $created = $this->service->create($validated);

        return ApiResponse::success(
            new ExperienceLevelResource($created),
            __('Experience level created successfully'),
            201
        );
    }

    /**
     * Display the specified experience level.
     */
    public function show(Request $request)
    {
        $this->checkPermission('experience_levels.view');

        $experienceLevel = ExperienceLevel::findOrFail($request->route('experience_level'));

        return ApiResponse::success(
            new ExperienceLevelResource($experienceLevel),
            __('Experience level details retrieved')
        );
    }

    /**
     * Update the specified experience level in storage.
     */
    public function update(UpdateExperienceLevelRequest $request)
    {
        $this->checkPermission('experience_levels.update');

        $experienceLevel = ExperienceLevel::findOrFail($request->route('experience_level'));

        $validated = $request->validated();

        $updated = $this->service->update($experienceLevel, $validated);

        return ApiResponse::success(new ExperienceLevelResource($updated), __('Experience level updated successfully'));
    }

    /**
     * Remove the specified experience level from storage.
     */
    public function destroy(Request $request)
    {
        $this->checkPermission('experience_levels.delete');

        $experienceLevel = ExperienceLevel::findOrFail($request->route('experience_level'));

        $this->service->delete($experienceLevel);

        return ApiResponse::success(null, __('Experience level deleted successfully'), 200);
    }

    /**
     * Display a listing of archived experience levels.
     */
    public function archived()
    {
        $this->checkPermission('experience_levels.view');

        $data = $this->service->archived();

        return ApiResponse::success(
            ExperienceLevelResource::collection($data),
            __('Archived experience levels retrieved successfully')
        );
    }

    /**
     * Restore the specified archived experience level.
     */
    public function restore(Request $request)
    {
        $this->checkPermission('experience_levels.restore');

        $restored = $this->service->restore($request->route('experienceLevelId'));

        return ApiResponse::success(
            new ExperienceLevelResource($restored),
            __('Experience level restored successfully')
        );
    }

    /**
     * Permanently delete the specified experience level from storage.
     */
    public function forceDelete(Request $request)
    {
        $this->checkPermission('experience_levels.force_delete');

        $this->service->forceDelete($request->route('experienceLevelId'));

        return ApiResponse::success(null, __('Experience level permanently deleted'), 200);
    }
}
