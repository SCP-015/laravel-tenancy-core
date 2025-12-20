<?php

namespace App\Http\Controllers\Tenant;

use Illuminate\Http\Request;
use App\Http\Requests\Tenant\SubmitFeedbackRequest;
use App\Http\Requests\Tenant\SubmitPublicFeedbackRequest;
use App\Services\Tenant\FeedbackService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    protected $feedbackService;

    public function __construct(FeedbackService $feedbackService)
    {
        $this->feedbackService = $feedbackService;
    }

    /**
     * Menangani feedback dari pengguna yang sudah login.
     */
    public function submit(SubmitFeedbackRequest $request)
    {
        return $this->feedbackService->processFeedback($request);
    }

    /**
     * Menangani feedback dari pengguna publik (belum login).
     */
    public function submitPublic(SubmitPublicFeedbackRequest $request)
    {
        return $this->feedbackService->processPublicFeedback($request);
    }
}