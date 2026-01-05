<?php

namespace App\Http\Controllers\Tenant;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\DigitalSignature\CreateCARequest;
use App\Http\Requests\Tenant\DigitalSignature\CreateSessionRequest;
use App\Http\Requests\Tenant\DigitalSignature\IssueCertificateRequest;
use App\Http\Requests\Tenant\DigitalSignature\ScanQRRequest;
use App\Http\Requests\Tenant\DigitalSignature\SignDocumentRequest;
use App\Http\Requests\Tenant\DigitalSignature\VerifyDocumentRequest;
use App\Http\Resources\Tenant\CertificateAuthorityResource;
use App\Http\Resources\Tenant\DocumentResource;
use App\Http\Resources\Tenant\SignatureResource;
use App\Http\Resources\Tenant\SigningSessionResource;
use App\Http\Resources\Tenant\UserCertificateResource;
use App\Http\Resources\Tenant\VerifiedDocumentResource;
use App\Models\Tenant\Document;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\DefaultSignerService;
use App\Services\Tenant\DigitalSignatureService;
use App\Services\Tenant\DocumentSigningService;
use App\Services\Tenant\DocumentVerificationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DigitalSignatureController extends Controller
{
    protected DigitalSignatureService $digitalSignatureService;
    protected DocumentSigningService $documentSigningService;
    protected DocumentVerificationService $documentVerificationService;
    protected DefaultSignerService $defaultSignerService;

    public function __construct(
        DigitalSignatureService $digitalSignatureService,
        DocumentSigningService $documentSigningService,
        DocumentVerificationService $documentVerificationService,
        DefaultSignerService $defaultSignerService
    ) {
        $this->digitalSignatureService = $digitalSignatureService;
        $this->documentSigningService = $documentSigningService;
        $this->documentVerificationService = $documentVerificationService;
        $this->defaultSignerService = $defaultSignerService;
    }

    protected function getCurrentTenantUser(): ?TenantUser
    {
        $centralUser = auth('api')->user();
        if (!$centralUser) return null;

        return TenantUser::where('global_id', $centralUser->global_id)->first();
    }

    public function index(Request $request, $tenant)
    {
        try {
            $currentUser = $this->getCurrentTenantUser();
            $data = $this->digitalSignatureService->getDashboard($currentUser);

            if ($request->wantsJson()) {
                return ApiResponse::success([
                    'has_ca' => $data['has_ca'],
                    'ca_info' => $data['ca_info'],
                    'user' => $data['user'],
                    'certificates' => UserCertificateResource::collection($data['certificates'])->resolve(),
                    'pending_signatures' => SignatureResource::collection($data['pending_signatures'])->resolve(),
                    'signed_documents' => DocumentResource::collection($data['signed_documents'])->resolve(),
                    'available_signers' => $data['available_signers'],
                    'all_certificates' => UserCertificateResource::collection($data['all_certificates_raw'])->resolve(),
                ]);
            }

            return Inertia::render('DigitalSignature/Dashboard', [
                'meta' => ['requiresAuth' => true, 'parent_menu' => 'digital-signature'],
                'hasCA' => $data['has_ca'],
                'caIncluded' => $data['ca_info'],
                'myCertificates' => UserCertificateResource::collection($data['certificates'])->resolve(),
                'pendingSignatures' => SignatureResource::collection($data['pending_signatures'])->resolve(),
                'signedDocuments' => DocumentResource::collection($data['signed_documents'])->resolve(),
                'availableSigners' => $data['available_signers'],
                'allCertificates' => UserCertificateResource::collection($data['all_certificates_raw'])->resolve(),
                'isAdmin' => $data['user']['is_admin'] ?? false,
                'auth' => ['user' => $currentUser]
            ]);
        } catch (Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage());
            return $request->wantsJson() 
                ? response()->json(['message' => 'Failed to load dashboard'], 500)
                : back()->withErrors(['msg' => 'Failed to load dashboard.']);
        }
    }

    public function storeCA(CreateCARequest $request, $tenant)
    {
        try {
            $currentUser = $this->getCurrentTenantUser();
            if (!$currentUser || (!$currentUser->isSuperAdmin() && !$currentUser->isAdmin())) {
                throw new Exception('Unauthorized: Only administrators can create CA.');
            }

            $ca = $this->digitalSignatureService->createCA($request->validated());
            
            return $request->wantsJson()
                ? ApiResponse::success(new CertificateAuthorityResource($ca), 'CA created successfully', 201)
                : redirect()->route('digital-signature.index', ['tenant' => tenant('id')])->with('success', 'CA created successfully.');
        } catch (Exception $e) {
            Log::error('Create CA error: ' . $e->getMessage());
            return $request->wantsJson()
                ? response()->json(['message' => $e->getMessage()], 400)
                : back()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    public function issueCertificate(IssueCertificateRequest $request, $tenant)
    {
        try {
            $currentUser = $this->getCurrentTenantUser();
            $userId = $request->input('user_id') ?? $currentUser->id;
            
            if ($userId != $currentUser->id && (!$currentUser->isSuperAdmin() && !$currentUser->isAdmin())) {
                throw new Exception('Unauthorized: You can only issue certificates for yourself.');
            }

            $certificate = $this->digitalSignatureService->issueCertificate($request->validated(), $userId);

            return $request->wantsJson()
                ? ApiResponse::success(new UserCertificateResource($certificate), 'Certificate issued successfully', 201)
                : redirect()->route('digital-signature.index', ['tenant' => tenant('id')])->with('success', 'Certificate issued successfully.');
        } catch (Exception $e) {
            Log::error('Issue certificate error: ' . $e->getMessage());
            return $request->wantsJson()
                ? response()->json(['message' => $e->getMessage()], 400)
                : back()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    public function createSession(CreateSessionRequest $request, $tenant)
    {
        try {
            $currentUser = $this->getCurrentTenantUser();
            if (!$currentUser || (!$currentUser->isSuperAdmin() && !$currentUser->isAdmin())) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $session = $this->documentSigningService->createSession(
                $request->validated(),
                $request->file('file'),
                $currentUser
            );

            return ApiResponse::success(new SigningSessionResource($session), 'Signing session created successfully', 201);
        } catch (Exception $e) {
            Log::error('Create session error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function signDocument(SignDocumentRequest $request, $tenant, $signatureId)
    {
        try {
            $currentUser = $this->getCurrentTenantUser();
            $signature = $this->documentSigningService->executeSigning(
                (int)$signatureId,
                (int)$request->input('certificate_id'),
                $currentUser
            );

            return ApiResponse::success(new SignatureResource($signature), 'Document signed successfully');
        } catch (Exception $e) {
            Log::error('Sign document error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function downloadDocument($tenant, $documentId)
    {
        $doc = Document::findOrFail($documentId);
        $filePath = $doc->signed_file_path ?? $doc->original_file_path;
        
        // Use local fallback if not in public
        $fullPath = Storage::disk('public')->exists($filePath) 
            ? Storage::disk('public')->path($filePath)
            : Storage::disk('local')->path($filePath);

        return response()->download($fullPath, $doc->filename);
    }

    public function verifyDocument($tenant, $documentId)
    {
        $doc = Document::with(['signatures.user', 'signatures.userCertificate'])->findOrFail($documentId);
        return ApiResponse::success((new VerifiedDocumentResource($doc))->resolve());
    }

    public function verifyPage($tenant)
    {
        return Inertia::render('DigitalSignature/Verify', [
            'meta' => ['requiresAuth' => true, 'parent_menu' => 'digital-signature']
        ]);
    }

    public function verifyUploadedFile(VerifyDocumentRequest $request, $tenant)
    {
        try {
            $result = $this->documentVerificationService->verifyUploadedFile($request->file('file'));
            if ($request->wantsJson()) {
                return response()->json($result, $result['success'] ? 200 : 400);
            }
            return Inertia::render('DigitalSignature/Verify', ['uploadResult' => $result]);
        } catch (Exception $e) {
            Log::error('Verify uploaded file error: ' . $e->getMessage());
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 500)
                : back()->withErrors(['msg' => 'Verification failed: ' . $e->getMessage()]);
        }
    }

    public function scanQRCode(ScanQRRequest $request, $tenant)
    {
        try {
            return response()->json($this->documentVerificationService->scanQR($request->input('qr_data')));
        } catch (Exception $e) {
            Log::error('Scan QR error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

}
