<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CertificateAuthority;
use App\Models\Tenant\DefaultSigner; // Added
use App\Models\Tenant\Document;
use App\Models\Tenant\Signature;
use App\Models\Tenant\SigningSession;
use App\Models\Tenant\UserCertificate;
use App\Models\Tenant\User as TenantUser;
use App\Services\PDFService;
use App\Services\PKIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Exception;

class DigitalSignatureController extends Controller
{
    protected $pkiService;
    protected $pdfService;

    public function __construct(PKIService $pkiService, PDFService $pdfService)
    {
        $this->pkiService = $pkiService;
        $this->pdfService = $pdfService;
    }

    /**
     * Get current authenticated user from TENANT database.
     * This is critical for multi-tenant isolation.
     * 
     * @return TenantUser|null
     */
    protected function getCurrentTenantUser(): ?TenantUser
    {
        // Get authenticated user from API guard (sistem menggunakan API auth dengan cookie)
        $centralUser = auth('api')->user();

        if (!$centralUser) {
            Log::warning('DigitalSignatureController: No authenticated user found in API guard');
            return null;
        }

        // Find corresponding user in TENANT database using global_id
        $tenantUser = TenantUser::where('global_id', $centralUser->global_id)->first();

        if (!$tenantUser) {
            Log::warning('DigitalSignatureController: User not found in tenant database', [
                'central_user_id' => $centralUser->id,
                'global_id' => $centralUser->global_id,
                'tenant_id' => tenant('id'),
            ]);
            return null;
        }

        Log::info('DigitalSignatureController: Resolved tenant user', [
            'tenant_user_id' => $tenantUser->id,
            'tenant_user_email' => $tenantUser->email,
            'tenant_id' => tenant('id'),
        ]);

        return $tenantUser;
    }

    // --- Dashboard ---
    public function index(Request $request, $tenant = null)
    {
        // Check if Root CA exists
        $ca = CertificateAuthority::where('is_revoked', false)->first();

        // Get authenticated user from TENANT database (critical for multi-tenant isolation)
        $currentUser = $this->getCurrentTenantUser();

        if (!$currentUser && $request->wantsJson()) {
             return response()->json(['message' => 'User not found in tenant database'], 404);
        }

        // Jika user tidak ditemukan, tetap tampilkan CA info tapi dengan data user kosong
        // Frontend middleware akan handle redirect ke login via meta requiresAuth
        $isAdmin = $currentUser ? ($currentUser->isSuperAdmin() || $currentUser->isAdmin()) : false;
        
        // My Certificates - hanya jika user ada
        $myCerts = $currentUser ? UserCertificate::where('user_id', $currentUser->id)
            ->where('is_revoked', false)
            ->get() : collect([]);
        
        // Pending Signatures - hanya jika user ada
        $pendingSignatures = $currentUser ? Signature::where('user_id', $currentUser->id)
            ->where('status', 'pending')
            ->with(['document', 'signingSession'])
            ->get()
            ->filter(function($sig) {
                $session = $sig->signingSession;
                // Untuk mode sequential, hanya tampilkan jika ini giliran user
                if ($session && $session->mode === 'sequential') {
                    return $sig->step_order === $session->current_step_order;
                }
                // Untuk parallel/hybrid, tampilkan semua pending
                return true;
            })
            ->values() : collect([]);

        // If Admin, get ALL certificates in tenant for management
        $allCertificates = $isAdmin 
            ? UserCertificate::with('user')->get()->map(function($cert) {
                return [
                    'id' => $cert->id,
                    'user_id' => $cert->user_id,
                    'user_name' => $cert->user ? $cert->user->name : 'Unknown User',
                    'label' => $cert->label,
                    'common_name' => $cert->common_name,
                    'email' => $cert->email,
                    'valid_from' => $cert->valid_from,
                    'valid_to' => $cert->valid_to,
                    'is_revoked' => $cert->is_revoked,
                ];
            })
            : collect([]);
        
        // Get user IDs who have certificates
        $userIdsWithCerts = UserCertificate::where('is_revoked', false)->pluck('user_id')->unique()->toArray();

        // Get all users in current TENANT database (not central!)
        $usersWithCerts = TenantUser::all()->map(function($user) use ($userIdsWithCerts) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'has_certificate' => in_array($user->id, $userIdsWithCerts),
                ];
            })->values();
            
        // Sanitize sensitive data before sending to frontend
        $sanitizedCerts = $myCerts->map(function($cert) {
            return [
                'id' => $cert->id,
                'label' => $cert->label,
                'common_name' => $cert->common_name,
                'email' => $cert->email,
                'valid_from' => $cert->valid_from,
                'valid_to' => $cert->valid_to,
                'is_revoked' => $cert->is_revoked,
                // DO NOT send: certificate_path, private_key_path, passphrase, serial_number
            ];
        });
        
        // Signed Documents (documents where current user has signed) - hanya jika user ada
        // Show documents even if not all signers have signed yet
        $signedDocs = $currentUser ? Document::whereHas('signatures', function($sq) use ($currentUser) {
                $sq->where('user_id', $currentUser->id)
                   ->where('status', 'signed'); // User has signed this document
            })
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function($doc) {
                return [
                    'id' => $doc->id,
                    'title' => $doc->title,
                    'filename' => $doc->filename,
                    'signed_at' => $doc->updated_at->toDateTimeString(),
                    'download_url' => route('digital-signature.download', ['tenant' => tenant('id'), 'document' => $doc->id]),
                    'verify_url' => route('digital-signature.verify', ['tenant' => tenant('id'), 'document' => $doc->id]),
                ];
            }) : collect([]);

        // Get Approval Templates from DefaultSigner Workgroups
        $templates = DefaultSigner::with('user')
            ->where('is_active', true)
            ->orderBy('workgroup')
            ->orderBy('step_order')
            ->get()
            ->groupBy('workgroup')
            ->map(function($signers, $workgroup) {
                return [
                    'id' => $workgroup, // Workgroup name as ID
                    'name' => $workgroup,
                    'description' => 'Default workflow for ' . $workgroup,
                    'steps' => $signers->map(function($signer) {
                        return [
                            'user_id' => $signer->user_id,
                            'name' => $signer->user ? $signer->user->name : 'Unknown',
                            'role' => $signer->role,
                            'step_order' => $signer->step_order
                        ];
                    })->values()
                ];
            })->values();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'has_ca' => (bool)$ca ? true : false,
                    'ca_info' => $ca ? ['name' => $ca->name, 'common_name' => $ca->common_name] : null,
                    'user' => $currentUser ? [
                        'id' => $currentUser->id,
                        'name' => $currentUser->name,
                        'email' => $currentUser->email,
                        'is_admin' => $isAdmin
                    ] : null,
                    'certificates' => $sanitizedCerts,
                    'pending_signatures' => $pendingSignatures,
                    'signed_documents' => $signedDocs,
                    'available_signers' => $usersWithCerts,
                    'templates' => $templates
                ]
            ]);
        }

        return Inertia::render('DigitalSignature/Dashboard', [
            'meta' => [
                'requiresAuth' => true,
                'parent_menu' => 'digital-signature'
            ],
            'hasCA' => (bool)$ca,
            'caIncluded' => $ca ? [
                'id' => $ca->id,
                'name' => $ca->name,
                'common_name' => $ca->common_name,
                'valid_from' => $ca->valid_from,
                'valid_to' => $ca->valid_to,
                // DO NOT send: certificate_path, private_key_path, serial_number
            ] : null,
            'myCertificates' => $sanitizedCerts,
            'pendingSignatures' => $pendingSignatures,
            'signedDocuments' => $signedDocs,
            'availableSigners' => $usersWithCerts,
            'templates' => $templates,
            'allCertificates' => $allCertificates, // For Admin view
            'isAdmin' => $isAdmin,
            'auth' => [
                'user' => $currentUser
            ]
        ]);
    }

    // --- CA Management ---
    public function storeCA(Request $request, $tenant)
    {
        // Only Admin can create CA
        $currentUser = $this->getCurrentTenantUser();
        if (!$currentUser || (!$currentUser->isSuperAdmin() && !$currentUser->isAdmin())) {
            if ($request->wantsJson()) return response()->json(['message' => 'Unauthorized. Only Admin can create Root CA.'], 403);
            return back()->withErrors(['msg' => 'Only administrators can create Root CA.']);
        }

        $validated = $request->validate([
            'common_name' => 'required|string|max:255',
            'organization' => 'required|string|max:255',
            'country' => 'sometimes|string|size:2', 
            'province' => 'sometimes|string|max:255',
            'locality' => 'sometimes|string|max:255',
            'email' => 'sometimes|email',
            'valid_days' => 'sometimes|integer|min:365|max:3650',
        ]);

        if (CertificateAuthority::exists()) {
            if ($request->wantsJson()) return response()->json(['message' => 'Root CA already exists for this tenant.'], 400);
            return back()->withErrors(['msg' => 'Root CA already exists for this tenant.']);
        }

        try {
            // Use provided values or defaults for Web form
            $caData = $this->pkiService->createRootCA(
                $validated['common_name'], 
                $validated['organization'],
                $validated['country'] ?? 'ID',
                $validated['province'] ?? 'Jakarta',
                $validated['locality'] ?? 'Jakarta',
                $validated['email'] ?? null,
                $validated['valid_days'] ?? 3650
            );
            
            // Store files
            $certPath = 'tenants/' . tenant('id') . '/ca/root.crt';
            $keyPath = 'tenants/' . tenant('id') . '/ca/root.key';
            
            Storage::put($certPath, $caData['certificate']);
            Storage::put($keyPath, $caData['private_key']); // In production, encrypt this!

            CertificateAuthority::create([
                'name' => $validated['organization'],
                'common_name' => $validated['common_name'],
                'serial_number' => $caData['serial_number'],
                'valid_from' => $caData['valid_from'],
                'valid_to' => $caData['valid_to'],
                'certificate_path' => $certPath,
                'private_key_path' => $keyPath,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Root CA created successfully',
                    'data' => [
                        'name' => $validated['organization'],
                        'common_name' => $validated['common_name']
                    ]
                ]);
            }

            return redirect()->route('digital-signature.index', ['tenant' => $tenant])->with('success', 'Root CA created successfully.');
        } catch (Exception $e) {
            if ($request->wantsJson()) return response()->json(['message' => $e->getMessage()], 500);
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    // --- User Certificate ---
    public function issueCertificate(Request $request, $tenant)
    {
        $request->validate([
            'passphrase_hash' => 'required|string|size:64',
            'label' => 'required|string|max:255',
        ]);

        // Get authenticated user from TENANT database
        $user = $this->getCurrentTenantUser();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        $ca = CertificateAuthority::first();
        
        if (!$ca) {
            \Illuminate\Support\Facades\Log::error('issueCertificate: No CA found');
            return back()->withErrors(['msg' => 'No Certificate Authority found']);
        }

        try {
            \Illuminate\Support\Facades\Log::info('issueCertificate: Starting for user ' . $user->id);
            
            // Load CA assets
            $caCert = Storage::get($ca->certificate_path);
            $caKey = Storage::get($ca->private_key_path);

            $certData = $this->pkiService->createUserCertificate(
                $caCert,
                $caKey,
                $user->name,
                $user->email,
                $request->passphrase_hash // Use hashed passphrase
            );
            
            \Illuminate\Support\Facades\Log::info('issueCertificate: Certificate created with serial ' . $certData['serial_number']);
            
            $certPath = 'tenants/' . tenant('id') . '/certs/' . $user->id . '/' . $certData['serial_number'] . '.crt';
            $keyPath = 'tenants/' . tenant('id') . '/certs/' . $user->id . '/' . $certData['serial_number'] . '.key';

            Storage::put($certPath, $certData['certificate']);
            Storage::put($keyPath, $certData['private_key']);
            
            \Illuminate\Support\Facades\Log::info('issueCertificate: Files saved');

            $cert = UserCertificate::create([
                'user_id' => $user->id,
                'label' => $request->label,
                'certificate_authority_id' => $ca->id,
                'serial_number' => $certData['serial_number'],
                'common_name' => $user->name,
                'email' => $user->email,
                'valid_from' => $certData['valid_from'],
                'valid_to' => $certData['valid_to'],
                'certificate_path' => $certPath,
                'private_key_path' => $keyPath,
                'passphrase' => bcrypt($request->passphrase_hash), // Hash the already-hashed passphrase
            ]);
            
            \Illuminate\Support\Facades\Log::info('issueCertificate: DB record created with ID ' . $cert->id);

            // Use Inertia location redirect to preserve tenant context
            return Inertia::location(route('digital-signature.index', ['tenant' => $tenant]));
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('issueCertificate error: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    // --- Document & Signing ---
    public function createSession(Request $request, $tenant)
    {
        // Get authenticated user from TENANT database
        $currentUser = $this->getCurrentTenantUser();
        if (!$currentUser) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Only Admin can create signing sessions
        if (!$currentUser->isSuperAdmin() && !$currentUser->isAdmin()) {
            return back()->withErrors(['msg' => 'Only administrators can create signing sessions.']);
        }

        $request->validate([
            'file' => 'required|mimes:pdf|max:10240',
            'title' => 'required|string',
            'mode' => 'required|in:sequential,parallel,hybrid',
            'signers' => 'required|array', // [{user_id, role}]
        ]);

        DB::beginTransaction();
        try {
            $userId = $currentUser->id;

            \Illuminate\Support\Facades\Log::info('createSession: Starting for user ' . $userId);

            // 1. Upload Document
            $file = $request->file('file');
            $path = $file->store('tenants/' . tenant('id') . '/documents');
            $hash = hash_file('sha256', $file->getRealPath());

            $doc = Document::create([
                'user_id' => $userId,
                'title' => $request->title,
                'filename' => $file->getClientOriginalName(),
                'original_file_path' => $path,
                'original_hash' => $hash,
                'status' => 'pending',
                'metadata' => ['size' => $file->getSize()]
            ]);

            \Illuminate\Support\Facades\Log::info('createSession: Document created with ID ' . $doc->id);

            // 2. Create Session
            $session = SigningSession::create([
                'document_id' => $doc->id,
                'title' => $request->title,
                'mode' => $request->mode,
                'created_by' => $userId,
                'status' => 'in_progress',
                'current_step_order' => 1
            ]);

            \Illuminate\Support\Facades\Log::info('createSession: Session created with ID ' . $session->id);

            // 3. Add Signers
            foreach ($request->signers as $index => $signer) {
                Signature::create([
                    'signing_session_id' => $session->id,
                    'user_id' => $signer['user_id'],
                    'document_id' => $doc->id,
                    'role' => $signer['role'] ?? 'Signer',
                    'step_order' => $index + 1, // Simple sequential order mapping
                    'status' => 'pending'
                ]);
            }
            
            \Illuminate\Support\Facades\Log::info('createSession: Signatures created');
            
            DB::commit();
            
            // Use Inertia location redirect to preserve tenant context
            return Inertia::location(route('digital-signature.index', ['tenant' => $tenant]));
        } catch (Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('createSession error: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    public function signDocument(Request $request, $tenant, string $signatureId)
    {
        $request->validate([
            'passphrase_hash' => 'required|string|size:64',
            'agreement' => 'required|accepted',
            'certificate_id' => 'required|exists:user_certificates,id'
        ]);

        $signature = Signature::findOrFail($signatureId);

        // Get authenticated user from TENANT database
        $currentUser = $this->getCurrentTenantUser();

        if (!$currentUser) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Check if user is the assigned signer
        if ($signature->user_id != $currentUser->id) {
            Log::error("signDocument: Unauthorized. Signature user " . $signature->user_id . " vs current user " . $currentUser->id);
            return back()->withErrors(['msg' => 'You are not authorized to sign this document.']);
        }

        // Get the specific certificate - MUST belong to current user (prevent using other's cert)
        $cert = UserCertificate::where('user_id', $currentUser->id)
            ->where('id', $request->certificate_id)
            ->where('is_revoked', false)
            ->first();

        if (!$cert) {
            Log::error("signDocument: Certificate not found or doesn't belong to user", [
                'requested_cert_id' => $request->certificate_id,
                'user_id' => $currentUser->id,
            ]);
            return back()->withErrors(['msg' => 'Certificate not found or does not belong to you.']);
        }
            
            
        // Validate session state (Sequential check)
        $session = $signature->signingSession;
        
        \Illuminate\Support\Facades\Log::info("signDocument: Sequential Check", [
            'session_mode' => $session->mode,
            'current_step_order' => $session->current_step_order,
            'signature_step_order' => $signature->step_order,
            'user_trying_to_sign' => $currentUser->id,
            'signature_assigned_to' => $signature->user_id,
        ]);
        
        if ($session->mode === 'sequential' && $session->current_step_order != $signature->step_order) {
            \Illuminate\Support\Facades\Log::warning("signDocument: Sequential order violation!", [
                'expected_step' => $session->current_step_order,
                'attempted_step' => $signature->step_order,
            ]);
            return back()->withErrors(['msg' => 'It is not your turn to sign yet. Please wait for previous signers.']);
        }

        try {
            \Illuminate\Support\Facades\Log::info("signDocument: Signing signature " . $signature->id . " for user " . $currentUser->name);

            // 1. Get Private Key
            $keyContent = Storage::get($cert->private_key_path);
            
            // 2. Calculate Hash of the Document (use the CURRENT file, which might be partially signed)
            $doc = $signature->document;
            $currentFilePath = $doc->signed_file_path ? $doc->signed_file_path : $doc->original_file_path;
            
            \Illuminate\Support\Facades\Log::info("signDocument: Hashing file " . $currentFilePath);
            
            $absolutePath = Storage::path($currentFilePath);
            $fileContent = file_get_contents($absolutePath);
            $hash = hash('sha256', $fileContent);

            // 3. Sign the Hash
            \Illuminate\Support\Facades\Log::info("signDocument: Attempting with passphrase_hash length: " . strlen($request->passphrase_hash));
            $sigString = $this->pkiService->signData($hash, $keyContent, $request->passphrase_hash);
            if (!$sigString) {
                throw new Exception("Signing failed. Check passphrase.");
            }

            // 4. Save Signature File (.sig)
            $sigPath = 'tenants/' . tenant('id') . '/signatures/' . $signature->id . '.sig';
            Storage::put($sigPath, $sigString);

            // 5. Watermark PDF
            $newPdfPath = 'tenants/' . tenant('id') . '/documents/' . $doc->id . '_v' . $signature->step_order . '.pdf';
            $absoluteNewPath = Storage::path($newPdfPath);
            
            // Ensure dir exists
            $dir = dirname($absoluteNewPath);
            if (!file_exists($dir)) mkdir($dir, 0755, true);

            \Illuminate\Support\Facades\Log::info("signDocument: Watermarking PDF to " . $newPdfPath);

            $verifyUrl = route('digital-signature.verify', ['tenant' => $tenant, 'document' => $doc->id]);

            // Calculate total signers for positioning
            $totalSigners = $session->signatures()->count();
            $currentSignerIndex = $signature->step_order - 1; // 0-based index

            // Add signature watermark:
            // - Always on LAST page
            // - Bottom-right corner
            // - Auto-stacked if multiple signers
            $this->pdfService->addWatermarks($absolutePath, $absoluteNewPath, [[
                'text' => "Digitally Signed by " . $currentUser->name,
                'signer_name' => $currentUser->name,
                'date' => date('Y-m-d H:i:s'),
                'page' => 'last', // Always last page
                'position' => 'bottom-right', // Auto position in bottom-right
                'signer_index' => $currentSignerIndex, // For stacking calculation
                'total_signers' => $totalSigners,
                'qr_data' => $verifyUrl
            ]]);
            
            // 6. Update DB
            $signature->update([
                'status' => 'signed',
                'signed_at' => now(),
                'signature_file_path' => $sigPath,
                'certificate_id' => $cert->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            $doc->update([
                'signed_file_path' => $newPdfPath,
                'current_hash' => hash_file('sha256', $absoluteNewPath)
            ]);
            
            \Illuminate\Support\Facades\Log::info("signDocument: DB records updated successfully");

            // Update Session
            if ($session->mode === 'sequential') {
                $session->increment('current_step_order');
            }
            
            // Check if all signed
            if ($session->signatures()->where('status', '!=', 'signed')->count() === 0) {
                $session->update(['status' => 'completed']);
                $doc->update(['status' => 'signed']);
            }

            \Illuminate\Support\Facades\Log::info("signDocument: Redirecting back to index");
            return Inertia::location(route('digital-signature.index', ['tenant' => $tenant]));

        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error("signDocument error: " . $e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    public function downloadDocument($tenant, string $documentId)
    {
        $doc = Document::findOrFail($documentId);
        
        if (!$doc->signed_file_path || !Storage::exists($doc->signed_file_path)) {
            abort(404, "Signed document not found");
        }

        return Storage::download($doc->signed_file_path, $doc->filename);
    }

    public function verifyDocument($tenant, string $documentId)
    {
        $doc = Document::with(['signatures.user', 'signatures.userCertificate'])->findOrFail($documentId);
        
        $logEntries = Signature::where('document_id', $doc->id)
            ->where('status', 'signed')
            ->with(['user', 'userCertificate'])
            ->get()
            ->map(function($sig) {
                return [
                    'signer' => $sig->user->name,
                    'signed_at' => $sig->signed_at,
                    'serial' => $sig->userCertificate->serial_number,
                    'ip' => $sig->ip_address
                ];
            });

        return Inertia::render('DigitalSignature/Verify', [
            'document' => [
                'title' => $doc->title,
                'filename' => $doc->filename,
                'current_hash' => $doc->current_hash,
                'status' => $doc->status,
            ],
            'signatures' => $logEntries
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

    public function verifyUploadedFile(Request $request, $tenant)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240'
        ]);

        $file = $request->file('file');
        $hash = hash_file('sha256', $file->getRealPath());

        // ✅ VALID = Hash matches CURRENT_HASH (signed version with QR code)
        $doc = Document::where('current_hash', $hash)->first();

        // Check if this is an original (unsigned) file
        $isOriginalFile = false;
        if (!$doc) {
            // Check if hash matches an original_hash (unsigned version)
            $originalDoc = Document::where('original_hash', $hash)->first();
            if ($originalDoc) {
                $isOriginalFile = true;
                $doc = $originalDoc; // For showing document info in error
            }
        }

        // Check for valid signatures (only if doc found via current_hash)
        $logEntries = collect([]);
        if ($doc && !$isOriginalFile) {
            $logEntries = Signature::where('document_id', $doc->id)
                ->where('status', 'signed')
                ->with(['user', 'userCertificate'])
                ->get()
                ->map(function($sig) {
                    return [
                        'signer' => $sig->user->name,
                        'signed_at' => $sig->signed_at,
                        'serial' => $sig->userCertificate->serial_number,
                        'ip' => $sig->ip_address
                    ];
                });
        }

        // ONLY valid if:
        // 1. Hash matches current_hash (not original_hash)
        // 2. Document has at least 1 signature
        $isValid = $doc && !$isOriginalFile && $logEntries->isNotEmpty();

        // Special case: Document marked as "signed" but no signature records
        // This can happen if signature records were deleted but document wasn't cleaned up
        $isOrphanedSigned = $doc && $doc->status === 'signed' && $logEntries->isEmpty();

        if ($isValid) {
            // ✅ VALID - Signed document with QR code
            return Inertia::render('DigitalSignature/Verify', [
                'uploadResult' => [
                    'success' => true,
                    'message' => 'Document Verified ✓',
                    'description' => 'This document has valid digital signatures and is authenticated.',
                    'document' => [
                        'title' => $doc->title,
                        'filename' => $doc->filename,
                        'current_hash' => $doc->current_hash,
                        'status' => $doc->status,
                    ],
                    'signatures' => $logEntries
                ]
            ]);
        } else if ($isOrphanedSigned) {
            // ⚠️ ORPHANED - Document marked signed but no signature records (data inconsistency)
            $invalidMessage = 'Document Status Inconsistent';
            $invalidDescription = 'This document appears to have been signed (has QR code), but signature records are missing from the database. This may occur if signature data was deleted. Please contact administrator.';

            return Inertia::render('DigitalSignature/Verify', [
                'uploadResult' => [
                    'success' => false,
                    'message' => $invalidMessage,
                    'description' => $invalidDescription,
                    'filename' => $file->getClientOriginalName(),
                    'document' => [
                        'title' => $doc->title,
                        'filename' => $doc->filename,
                        'current_hash' => $doc->current_hash,
                        'status' => $doc->status,
                    ],
                    'signatures' => []
                ]
            ]);
        } else {
            // ❌ INVALID - Everything else
            if ($isOriginalFile) {
                // Special case: This is the original UNSIGNED file
                // Don't show document details - this is the raw file before signing
                $invalidMessage = 'Original Unsigned Document';
                $invalidDescription = 'This is the original document before signing. It does not contain any QR code or digital signatures. Please upload the SIGNED version to verify authenticity.';
                
                return Inertia::render('DigitalSignature/Verify', [
                    'uploadResult' => [
                        'success' => false,
                        'message' => $invalidMessage,
                        'description' => $invalidDescription,
                        'filename' => $file->getClientOriginalName(),
                        'document' => null, // Don't show DB document info for original file
                        'signatures' => []
                    ]
                ]);
            } else if (!$doc) {
                // File not in database at all
                $invalidMessage = 'Document Not Found';
                $invalidDescription = 'This file is not registered in our system. It may have never been uploaded for signing.';
            } else {
                // Document exists but no signatures
                $invalidMessage = 'Document Invalid or Not Signed';
                $invalidDescription = 'This document has been uploaded but does not have any valid digital signatures.';
            }

            return Inertia::render('DigitalSignature/Verify', [
                'uploadResult' => [
                    'success' => false,
                    'message' => $invalidMessage,
                    'description' => $invalidDescription,
                    'filename' => $file->getClientOriginalName(),
                    'document' => $doc ? [
                        'title' => $doc->title,
                        'filename' => $doc->filename,
                        'current_hash' => $doc->current_hash,
                        'status' => $doc->status,
                    ] : null,
                    'signatures' => []
                ]
            ]);
        }
    }
}
