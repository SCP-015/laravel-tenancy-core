<?php

namespace App\Http\Controllers\Tenant;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\DigitalSignature\CreateCARequest;
use App\Http\Requests\Tenant\DigitalSignature\CreateSessionRequest;
use App\Http\Requests\Tenant\DigitalSignature\IssueCertificateRequest;
use App\Http\Requests\Tenant\DigitalSignature\ScanQRRequest;
use App\Http\Requests\Tenant\DigitalSignature\SetDefaultSignerRequest;
use App\Http\Requests\Tenant\DigitalSignature\SignDocumentRequest;
use App\Http\Requests\Tenant\DigitalSignature\VerifyDocumentRequest;
use App\Http\Resources\Tenant\CertificateAuthorityResource;
use App\Http\Resources\Tenant\SigningSessionResource;
use App\Http\Resources\Tenant\UserCertificateResource;
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

        if (!$centralUser) {
            Log::warning('DigitalSignatureController: No authenticated user found in API guard');
            return null;
        }

        $tenantUser = TenantUser::where('global_id', $centralUser->global_id)->first();

        if (!$tenantUser) {
            Log::warning('DigitalSignatureController: User not found in tenant database', [
                'central_user_id' => $centralUser->id,
                'global_id' => $centralUser->global_id,
                'tenant_id' => tenant('id'),
            ]);
            return null;
        }

        return $tenantUser;
    }

    public function index(Request $request, $tenant = null)
    {
        try {
            $currentUser = $this->getCurrentTenantUser();
            $data = $this->digitalSignatureService->getDashboard($currentUser);

            if ($request->wantsJson()) {
                return response()->json($data);
            }
            $ca = $data['has_ca'] ? \App\Models\Tenant\CertificateAuthority::where('is_revoked', false)->first() : null;

            return Inertia::render('DigitalSignature/Dashboard', [
                'meta' => [
                    'requiresAuth' => true,
                    'parent_menu' => 'digital-signature'
                ],
                'hasCA' => $data['has_ca'],
                'caIncluded' => $ca ? [
                    'id' => $ca->id,
                    'name' => $ca->name,
                    'common_name' => $ca->common_name,
                    'valid_from' => $ca->valid_from,
                    'valid_to' => $ca->valid_to,
                ] : null,
                'myCertificates' => $data['certificates'],
                'pendingSignatures' => $data['pending_signatures'],
                'signedDocuments' => $data['signed_documents'],
                'availableSigners' => $data['available_signers'],
                'templates' => [],
                'allCertificates' => $data['all_certificates'],
                'isAdmin' => $data['user']['is_admin'] ?? false,
                'auth' => [
                    'user' => $currentUser
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage());
            
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Failed to load dashboard'], 500);
            }

            return back()->withErrors(['msg' => 'Failed to load dashboard.']);
        }
    }

    public function storeCA(CreateCARequest $request, $tenant)
    {
        try {
            $currentUser = $this->getCurrentTenantUser();
            if (!$currentUser || (!$currentUser->isSuperAdmin() && !$currentUser->isAdmin())) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
                return back()->withErrors(['msg' => 'Only administrators can create Certificate Authority.']);
            }

            $ca = $this->digitalSignatureService->createCA($request->validated());

            if ($request->wantsJson()) {
                return ApiResponse::success(new CertificateAuthorityResource($ca), 'Certificate Authority created successfully', 201);
            }

            return redirect()->route('digital-signature.index', ['tenant' => tenant('id')])
                ->with('success', 'Certificate Authority created successfully.');
        } catch (Exception $e) {
            Log::error('Create CA error: ' . $e->getMessage());
            
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 400);
            }

            return back()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    public function issueCertificate(IssueCertificateRequest $request, $tenant)
    {
        try {
            $currentUser = $this->getCurrentTenantUser();
            if (!$currentUser) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'User not found'], 404);
                }
                return back()->withErrors(['msg' => 'User not found in tenant database.']);
            }

            $userId = $request->input('user_id') ?? $currentUser->id;
            
            if ($userId != $currentUser->id) {
                if (!$currentUser->isSuperAdmin() && !$currentUser->isAdmin()) {
                    if ($request->wantsJson()) {
                        return response()->json(['message' => 'Unauthorized'], 403);
                    }
                    return back()->withErrors(['msg' => 'You can only issue certificates for yourself.']);
                }
            }

            $certificate = $this->digitalSignatureService->issueCertificate($request->validated(), $userId);

            if ($request->wantsJson()) {
                return ApiResponse::success(new UserCertificateResource($certificate), 'Certificate issued successfully', 201);
            }

            return redirect()->route('digital-signature.index', ['tenant' => tenant('id')])
                ->with('success', 'Certificate issued successfully.');
        } catch (Exception $e) {
            Log::error('Issue certificate error: ' . $e->getMessage());
            
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 400);
            }

            return back()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    public function createSession(CreateSessionRequest $request, $tenant)
    {
        try {
            $currentUser = $this->getCurrentTenantUser();
            if (!$currentUser) {
                return response()->json(['message' => 'User not found'], 404);
            }

            if (!$currentUser->isSuperAdmin() && !$currentUser->isAdmin()) {
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

    public function signDocument(SignDocumentRequest $request, $tenant, string $signatureId)
    {
        try {
            $currentUser = $this->getCurrentTenantUser();
            if (!$currentUser) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $result = $this->documentSigningService->signDocument(
                (int)$signatureId,
                (int)$request->input('certificate_id'),
                $currentUser
            );

            return ApiResponse::success($result, 'Document signed successfully');
        } catch (Exception $e) {
            Log::error('Sign document error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function downloadDocument($tenant, string $documentId)
    {
        $doc = Document::findOrFail($documentId);
        
        $filePath = $doc->signed_file_path ?? $doc->original_file_path;
        $fullPath = Storage::disk('public')->path($filePath);

        return response()->download($fullPath, $doc->filename);
    }

    public function verifyDocument($tenant, string $documentId)
    {
        $doc = Document::with(['signatures.user', 'signatures.userCertificate'])->findOrFail($documentId);
        
        $signatures = $doc->signatures->map(function($sig) {
            $cert = $sig->userCertificate;
            return [
                'signer' => $sig->user->name,
                'signed_at' => $sig->signed_at,
                'certificate_serial' => $cert->serial_number,
                'certificate_valid_from' => $cert->valid_from,
                'certificate_valid_to' => $cert->valid_to,
            ];
        });

        return response()->json([
            'document' => [
                'title' => $doc->title,
                'filename' => $doc->filename,
                'status' => $doc->status,
            ],
            'signatures' => $signatures
        ]);
    }

    public function verifyPage($tenant)
    {
        return Inertia::render('DigitalSignature/Verify', [
            'meta' => [
                'requiresAuth' => true,
                'parent_menu' => 'digital-signature'
            ]
        ]);
    }

    public function verifyUploadedFile(VerifyDocumentRequest $request, $tenant)
    {
        try {
            $result = $this->documentVerificationService->verifyUploadedFile($request->file('file'));

            if ($request->wantsJson()) {
                $statusCode = $result['success'] ? 200 : 400;
                return response()->json($result, $statusCode);
            }

            return Inertia::render('DigitalSignature/Verify', [
                'uploadResult' => $result
            ]);
        } catch (Exception $e) {
            Log::error('Verify uploaded file error: ' . $e->getMessage());
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification failed',
                    'description' => $e->getMessage()
                ], 500);
            }

            return back()->withErrors(['msg' => 'Verification failed: ' . $e->getMessage()]);
        }
    }

    public function scanQRCode(ScanQRRequest $request)
    {
        try {
            $result = $this->documentVerificationService->scanQR($request->input('qr_data'));
            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Scan QR error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to scan QR code: ' . $e->getMessage()
            ], 500);
        }
    }
}
