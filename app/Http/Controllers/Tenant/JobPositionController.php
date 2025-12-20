<?php

namespace App\Http\Controllers\Tenant;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreJobPositionRequest;
use App\Http\Requests\Tenant\UpdateJobPositionRequest;
use App\Http\Resources\Tenant\JobPositionResource;
use App\Models\Tenant\JobPosition;
use App\Services\Tenant\JobPositionService;
use App\Traits\HasPermissionTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobPositionController extends Controller
{
    use HasPermissionTrait;

    protected $service;

    public function __construct(JobPositionService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the job positions.
     */
    public function index(Request $request)
    {
        $this->checkPermission('job_positions.view');

        $hasPaginationParams = $request->hasAny(['per_page', 'page', 'search']);

        // Mode lama: tanpa param pagination, kembalikan semua data (backward compatible)
        if (! $hasPaginationParams) {
            $data = JobPositionResource::collection($this->service->all());

            return ApiResponse::success($data, __('Job positions retrieved successfully'));
        }

        // Mode baru: dengan pagination & search
        $paginator = $this->service->paginate($request->all());
        $resourceCollection = JobPositionResource::collection($paginator);

        // Ambil array data dari resource collection
        $responseArray = $resourceCollection->response()->getData(true);

        return ApiResponse::success([
            'items' => $responseArray['data'] ?? [],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], __('Job positions retrieved successfully'));
    }

    /**
     * Store a newly created job position in storage.
     */
    public function store(StoreJobPositionRequest $request)
    {
        $this->checkPermission('job_positions.create');

        $validated = $request->validated();

        $jobPosition = $this->service->create($validated);

        return ApiResponse::success(
            new JobPositionResource($jobPosition),
            __('Job position created successfully'),
            201,
            'Created'
        );
    }

    /**
     * Display the specified job position.
     */
    public function show(Request $request)
    {
        $this->checkPermission('job_positions.view');

        $jobPosition = JobPosition::findOrFail($request->route('job_position'));
        $data = $this->service->show($jobPosition);

        return ApiResponse::success(new JobPositionResource($data), __('Job position details retrieved'));
    }

    /**
     * Update the specified job position in storage.
     */
    public function update(UpdateJobPositionRequest $request)
    {
        $this->checkPermission('job_positions.update');

        $jobPosition = JobPosition::findOrFail($request->route('job_position'));

        $validated = $request->validated();

        $updated = $this->service->update($jobPosition, $validated);

        return ApiResponse::success(new JobPositionResource($updated), __('Job position updated successfully'));
    }

    /**
     * Remove the specified job position from storage.
     */
    public function destroy(Request $request)
    {
        $this->checkPermission('job_positions.delete');

        $jobPosition = JobPosition::findOrFail($request->route('job_position'));
        $this->service->delete($jobPosition);

        return ApiResponse::success(null, __('Job position deleted successfully'), 200, 'Deleted');
    }

    /**
     * Display a listing of archived job positions.
     */
    public function archived()
    {
        $this->checkPermission('job_positions.view');

        $data = $this->service->archived();

        return ApiResponse::success(
            JobPositionResource::collection($data),
            __('Archived job positions retrieved successfully')
        );
    }

    /**
     * Restore the specified archived job position.
     */
    public function restore(Request $request)
    {
        $this->checkPermission('job_positions.restore');

        $jobPosition = $this->service->restore($request->route('jobPositionId'));

        return ApiResponse::success(new JobPositionResource($jobPosition), __('Job position restored successfully'));
    }

    /**
     * Permanently delete the specified job position from storage.
     */
    public function forceDelete(Request $request)
    {
        $this->checkPermission('job_positions.force_delete');

        $this->service->forceDelete($request->route('jobPositionId'));

        return ApiResponse::success(null, __('Job position permanently deleted'), 200, 'Deleted');
    }
}
