<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\Tenant\ListFeedbackService;
use Illuminate\Http\Request;

class ListFeedbackController extends Controller
{
    protected $listFeedbackService;

    public function __construct(ListFeedbackService $listFeedbackService)
    {
        $this->listFeedbackService = $listFeedbackService;
    }
    
    /**
     * Mendapatkan daftar feedback untuk user yang sedang login
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Cek apakah client meminta refresh data
        $forceRefresh = $request->has('refresh') && $request->input('refresh') === 'true';
        
        // Ambil data feedback dengan parameter force refresh
        $feedbackList = $this->listFeedbackService->getPersonalFeedback($forceRefresh);
        
        // Tambahkan header cache-control
        $headers = [];
        if (isset($feedbackList['cache_info']) && $feedbackList['cache_info']['from_cache']) {
            $maxAge = $feedbackList['cache_info']['expires_in'];
            $headers['Cache-Control'] = "public, max-age={$maxAge}";
        } else {
            $headers['Cache-Control'] = 'no-store, must-revalidate';
        }
        
        return response()->json($feedbackList, 200, $headers);
    }
}

