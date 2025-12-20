<?php

namespace App\Http\Controllers\Tenant;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreJobLevelRequest;
use App\Http\Requests\Tenant\UpdateJobLevelRequest;
use App\Http\Resources\Tenant\JobLevelResource;
use App\Models\Tenant\JobLevel;
use App\Services\Tenant\JobLevelService;
use App\Traits\HasPermissionTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobLevelController extends Controller
{
    use HasPermissionTrait;

    protected $service;

    public function __construct(JobLevelService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the job levels.
     */
    public function index()
    {
        $this->checkPermission('job_levels.view');

        $data = JobLevelResource::collection($this->service->all());

        return ApiResponse::success($data, __('Job levels retrieved successfully'));
    }

    /**
     * Store a newly created job level in storage.
     */
    public function store(StoreJobLevelRequest $request)
    {
        $this->checkPermission('job_levels.create');

        $validated = $request->validated();

        $created = $this->service->create($validated);

        return ApiResponse::success(new JobLevelResource($created), __('Job level created successfully'), 201);
    }

    /**
     * Display the specified job level.
     */
    public function show(Request $request)
    {
        $this->checkPermission('job_levels.view');

        $jobLevel = JobLevel::findOrFail($request->route('job_level'));

        return ApiResponse::success(new JobLevelResource($jobLevel), __('Job level details retrieved'));
    }

    /**
     * Update the specified job level in storage.
     */
    public function update(UpdateJobLevelRequest $request)
    {
        $this->checkPermission('job_levels.update');

        $jobLevel = JobLevel::findOrFail($request->route('job_level'));

        $validated = $request->validated();

        $updated = $this->service->update($jobLevel, $validated);

        return ApiResponse::success(new JobLevelResource($updated), __('Job level updated successfully'));
    }

    /**
     * Remove the specified job level from storage.
     */
    public function destroy(Request $request)
    {
        $this->checkPermission('job_levels.delete');

        $jobLevel = JobLevel::findOrFail($request->route('job_level'));

        $this->service->delete($jobLevel);

        return ApiResponse::success(null, __('Job level deleted successfully'), 200);
    }

    /**
     * Display a listing of archived job levels.
     */
    public function archived()
    {
        $this->checkPermission('job_levels.view');

        $data = $this->service->archived();

        return ApiResponse::success(
            JobLevelResource::collection($data), 
            __('Archived job levels retrieved successfully')
        );
    }

    /**
     * Restore the specified archived job level.
     */
    public function restore(Request $request)
    {
        $this->checkPermission('job_levels.restore');

        $restored = $this->service->restore($request->route('jobLevelId'));

        return ApiResponse::success(new JobLevelResource($restored), __('Job level restored successfully'));
    }

    /**
     * Permanently delete the specified job level from storage.
     */
    public function forceDelete(Request $request)
    {
        $this->checkPermission('job_levels.force_delete');

        $this->service->forceDelete($request->route('jobLevelId'));

        return ApiResponse::success(null, __('Job level permanently deleted'), 200);
    }
}
