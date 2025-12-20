<?php

namespace App\Http\Controllers;

use App\Services\AppConfigService;
use Illuminate\Http\JsonResponse;

class AppConfigController extends Controller
{
    private AppConfigService $appConfigService;

    public function __construct(AppConfigService $appConfigService)
    {
        $this->appConfigService = $appConfigService;
    }

    public function show(): JsonResponse
    {
        return response()->json([
            'data' => $this->appConfigService->getSlugCooldownConfig(),
        ]);
    }
}
