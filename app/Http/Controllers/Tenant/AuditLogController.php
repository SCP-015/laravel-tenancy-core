<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tenant\AuditLogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OwenIt\Auditing\Models\Audit;

class AuditLogController extends Controller
{

    /**
     * Get all audit logs dengan filters
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->get('per_page', 50);
        
        $audits = Audit::query()
            ->with('user:id,name,email')
            ->when($request->filled('model_type'), function ($query) use ($request) {
                // Filter by model type
                // Format: QuestionTemplate, JobVacancy, JobVacancyQuestion, etc
                $modelType = $request->input('model_type');

                // Khusus kelompok Variable: gabungkan beberapa model master data
                if ('Variable' === $modelType) {
                    $variableModels = [
                        'App\\Models\\Tenant\\JobPosition',
                        'App\\Models\\Tenant\\JobLevel',
                        'App\\Models\\Tenant\\EducationLevel',
                        'App\\Models\\Tenant\\ExperienceLevel',
                    ];

                    $query->whereIn('auditable_type', $variableModels);

                    return;
                }

                $fullModelType = "App\\Models\\Tenant\\{$modelType}";
                $query->where('auditable_type', $fullModelType);
            })
            ->when($request->filled('event'), function ($query) use ($request) {
                // Filter by event: created, updated, deleted, restored
                $query->where('event', $request->input('event'));
            })
            ->when($request->filled('user_id'), function ($query) use ($request) {
                // Filter by user
                $query->where('user_id', $request->input('user_id'));
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                // Filter by date range - from
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                // Filter by date range - to
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $driver = config('database.default');
                
                // Map Indonesian labels to database event types
                $eventMap = [
                    'dibuat' => 'created',
                    'diubah' => 'updated',
                    'dihapus' => 'deleted',
                    'dipulihkan' => 'restored',
                    'login' => 'login',
                ];
                
                // Map changes_summary keywords to event types
                $summaryMap = [
                    'data baru dibuat' => 'created',
                    'data dibuat' => 'created',
                    'baru dibuat' => 'created',
                    'data dihapus' => 'deleted',
                    'dihapus' => 'deleted',
                    'data dipulihkan' => 'restored',
                    'dipulihkan' => 'restored',
                    'mengubah' => 'updated',
                    'data diubah' => 'updated',
                    'login ke nusahire' => 'login',
                    'login admin baru' => 'login',
                    'login ke sistem' => 'login',
                ];
                
                // Get matching event types
                $searchLower = strtolower($search);
                $matchingEvents = [];
                
                // Check event labels
                foreach ($eventMap as $label => $event) {
                    if (strpos($label, $searchLower) !== false) {
                        $matchingEvents[] = $event;
                    }
                }
                
                // Check changes_summary keywords
                foreach ($summaryMap as $keyword => $event) {
                    if (strpos($keyword, $searchLower) !== false || strpos($searchLower, $keyword) !== false) {
                        if (!in_array($event, $matchingEvents)) {
                            $matchingEvents[] = $event;
                        }
                    }
                }
                
                $query->where(function ($q) use ($search, $matchingEvents, $driver) {
                    // Determine case-insensitive operator based on driver
                    $likeOp = $driver === 'pgsql' ? 'ilike' : 'like';
                    
                    // Search by user name/email
                    $q->whereHas('user', function ($userQuery) use ($search, $likeOp) {
                        $userQuery->where('name', $likeOp, "%{$search}%")
                            ->orWhere('email', $likeOp, "%{$search}%");
                    })
                    // Search by event type
                    ->orWhere('event', $likeOp, "%{$search}%");
                    
                    // If search matches Indonesian labels, also search by mapped events
                    if (!empty($matchingEvents)) {
                        $q->orWhereIn('event', $matchingEvents);
                    }
                    
                    // @codeCoverageIgnoreStart
                    // PostgreSQL-specific JSONB cast - tidak bisa di-test dengan SQLite
                    // Production menggunakan PostgreSQL, test menggunakan SQLite
                    if ($driver === 'pgsql') {
                        $q->orWhereRaw("old_values::text ilike ?", ["%{$search}%"])
                            ->orWhereRaw("new_values::text ilike ?", ["%{$search}%"]);
                    }
                    // @codeCoverageIgnoreEnd
                });
            })
            // Urutkan dari yang terbaru. Jika beberapa log punya timestamp detik yang sama,
            // gunakan ID sebagai tiebreaker supaya urutan lebih konsisten (log yang dibuat
            // paling akhir muncul paling atas).
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return AuditLogResource::collection($audits);
    }

    /**
     * Get available model types untuk filter dropdown
     * 
     * @return JsonResponse
     */
    public function getModelTypes(): JsonResponse
    {
        // Get distinct model types dari audit logs
        $rawTypes = Audit::query()
            ->select('auditable_type')
            ->distinct()
            ->pluck('auditable_type');

        $variableShortNames = [
            'JobPosition',
            'JobLevel',
            'EducationLevel',
            'ExperienceLevel',
        ];

        $hasVariable = false;
        $modelTypes = [];

        foreach ($rawTypes as $type) {
            // Extract class name dari full namespace
            // App\Models\Tenant\QuestionTemplate -> QuestionTemplate
            $parts = explode('\\', $type);
            $shortName = end($parts);

            // Tandai jika ada salah satu model Variable
            if (in_array($shortName, $variableShortNames, true)) {
                $hasVariable = true;
                continue;
            }

            $modelTypes[$shortName] = [
                'value' => $shortName,
                'label' => $this->getModelLabel($shortName),
            ];
        }

        // Tambahkan satu entry khusus untuk kelompok Variable jika ada minimal satu log
        if ($hasVariable) {
            $modelTypes['Variable'] = [
                'value' => 'Variable',
                'label' => 'Variable',
            ];
        }

        // Re-index untuk memastikan output berformat array numerik
        $modelTypes = array_values($modelTypes);

        return response()->json([
            'data' => $modelTypes,
        ]);
    }

    /**
     * Get Indonesian label untuk model type
     * 
     * @param string $modelType
     * @return string
     */
    private function getModelLabel(string $modelType): string
    {
        $labels = [
            // Variabel (master data) - disatukan sebagai "Variable"
            'JobPosition' => 'Variable',
            'JobLevel' => 'Variable',
            'EducationLevel' => 'Variable',
            'ExperienceLevel' => 'Variable',

            // User portal
            'User' => 'Pengguna',

            // Pengaturan portal (audit manual di tenant)
            'PortalSetting' => 'Company',
        ];

        return $labels[$modelType] ?? $modelType;
    }

    /**
     * Get available event types untuk filter dropdown
     * 
     * @return JsonResponse
     */
    public function getEventTypes(): JsonResponse
    {
        $events = [
            ['value' => 'created', 'label' => 'Dibuat'],
            ['value' => 'updated', 'label' => 'Diubah'],
            ['value' => 'deleted', 'label' => 'Dihapus'],
            ['value' => 'restored', 'label' => 'Dipulihkan'],
            ['value' => 'login', 'label' => 'Login'],
        ];

        return response()->json([
            'data' => $events,
        ]);
    }
}
