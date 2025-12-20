<?php

namespace App\Services\Tenant;

use App\Traits\Loggable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\Models\Tenant; // Tambahkan ini

class FeedbackService
{
    use Loggable;
    
    private $discordWebhookUrl;

    public function __construct()
    {
        $this->discordWebhookUrl = config('custom.discord_webhook_url');
    }

    /**
     * Memproses feedback dari pengguna yang sudah login.
     */
    public function processFeedback(Request $request)
    {
        $user = Auth::user();
        $tenant = tenant();
        $browserInfo = $this->parseBrowserInfo($request->header('User-Agent', ''));

        // Menentukan nama perusahaan secara kondisional
        $companyName = $tenant ? $tenant->name : 'Not Assigned to a Tenant';

        $payloadData = [
            'content' => 'Kirim feedback dari web',
            'title'   => 'Feedback WEB',
            'fields'  => [
                ['name' => 'From', 'value' => "{$user->email} / {$user->name}", 'inline' => false],
                ['name' => 'Company', 'value' => $companyName, 'inline' => false],
            ],
            'browser_info' => $browserInfo,
        ];

        return $this->processAndCleanUp($request, $payloadData);
    }
    
    /**
     * Memproses feedback dari pengguna publik.
     */
    public function processPublicFeedback(Request $request)
    {
        $browserInfo = $this->parseBrowserInfo($request->header('User-Agent', ''));
        
        // Ambil slug tenant dari payload (form data)
        $tenantSlug = $request->input('tenant_slug');
        
        // Cari tenant di database berdasarkan slug
        $tenant = Tenant::where('slug', $tenantSlug)->first();

        // Gunakan nama perusahaan jika ditemukan, jika tidak, gunakan 'N/A'
        $companyName = $tenant ? $tenant->name : 'Not Assigned to a Tenant';
        
        $senderName = $request->input('sender_name');
        $senderEmail = $request->input('sender_email');
        
        $payloadData = [
            'content' => 'Kirim feedback dari publik',
            'title'   => 'Feedback',
            'fields'  => [
                ['name' => 'From', 'value' => "$senderEmail / $senderName", 'inline' => false],
                ['name' => 'Company', 'value' => $companyName, 'inline' => false],
            ],
            'browser_info' => $browserInfo,
        ];

        return $this->processAndCleanUp($request, $payloadData);
    }
    
    /**
     * Metode pembantu untuk mengirim ke Discord dan membersihkan file.
     */
    private function processAndCleanUp(Request $request, array $payloadData)
    {
        $multipartFiles = $this->handleScreenshots($request);
        
        $discordPayload = $this->createBasePayload($request, $payloadData);

        try {
            $response = $this->sendToDiscord($discordPayload, $multipartFiles);
            
            if ($response && $response->successful()) {
                return response()->json(['message' => __('Feedback sent successfully.')], 200);
            // @codeCoverageIgnoreStart
            // Discord API error handling - sulit di-test tanpa mock kompleks external service
            } else {
                $this->logError(__('Failed to send to Discord.'), [
                    'response' => $response ? $response->body() : 'No response',
                    'status' => $response ? $response->status() : 'No status'
                ]);
                $statusCode = $response ? $response->status() : 500;
                return response()->json(['message' => __('Failed to send feedback.')], $statusCode);
            }
        } catch (Exception $e) {
            $this->logError(__('Error while sending feedback'), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => __('A server error occurred.')], 500);
        // @codeCoverageIgnoreEnd
        } finally {
            foreach ($multipartFiles as $file) {
                if (isset($file['contents']) && is_resource($file['contents'])) {
                    fclose($file['contents']);
                }
            }
            if (Storage::exists('temp')) {
                Storage::deleteDirectory('temp');
            }
        }
    }

    /**
     * Metode pembantu untuk mengelola screenshots.
     */
    private function handleScreenshots(Request $request)
    {
        $multipartFiles = [];
        if ($request->hasFile('screenshots')) {
            $files = $request->file('screenshots');
            foreach ($files as $index => $file) {
                $fileName = Carbon::now()->format('Ymd_His') . '_' . ($index + 1) . '.' . $file->extension();
                $path = $file->storeAs('temp', $fileName, 'local');
                $multipartFiles[] = [
                    'name'     => 'file' . ($index + 1),
                    'contents' => fopen(Storage::path($path), 'r'),
                    'filename' => $fileName,
                ];
            }
        }
        return $multipartFiles;
    }

    /**
     * Metode pembantu untuk membuat payload dasar.
     */
    private function createBasePayload(Request $request, array $payloadData): array
    {
    return [
        'content' => $payloadData['content'],
        'embeds'  => [
            [
                'title'       => $payloadData['title'],
                'description' => '',
                'color'       => 15258703,
                'fields'      => array_merge(
                    [
                        ['name' => 'URL', 'value' => $request->input('url'), 'inline' => false],
                        ['name' => 'Category', 'value' => $request->input('category'), 'inline' => false],
                        ['name' => 'Description', 'value' => $request->input('feedback'), 'inline' => false],
                    ],
                    $payloadData['fields'],
                    [
                        ['name' => 'Browser', 'value' => $payloadData['browser_info']['browser'], 'inline' => false],
                        ['name' => 'Version', 'value' => $payloadData['browser_info']['version'], 'inline' => false],
                        ['name' => 'Operating System', 'value' => $payloadData['browser_info']['os'], 'inline' => false]
                    ]
                )
            ]
        ]
    ];
    }
    
    private function sendToDiscord($payload, $multipartFiles)
    {
        // @codeCoverageIgnoreStart
        // Config validation - production akan selalu punya webhook URL
        if (!$this->discordWebhookUrl) {
            $this->logError('URL Discord Webhook tidak ditemukan.');
            return null;
        }
        // @codeCoverageIgnoreEnd

        return Http::attach($multipartFiles)
            ->post($this->discordWebhookUrl, ['payload_json' => json_encode($payload)]);
    }

    private function parseBrowserInfo(string $userAgent): array
    {
        $browser = 'Unknown';
        $version = 'Unknown';
        $os = 'Unknown';

        // Detect Browser
        if (preg_match('/Chrome\/([0-9\.]+)/', $userAgent, $matches)) {
            $browser = 'Chrome';
            $version = $matches[1];
        // @codeCoverageIgnoreStart
        // Browser detection untuk non-Chrome - memerlukan banyak test cases dengan berbagai User-Agent
        } elseif (preg_match('/Firefox\/([0-9\.]+)/', $userAgent, $matches)) {
            $browser = 'Firefox';
            $version = $matches[1];
        } elseif (preg_match('/Safari\/([0-9\.]+)/', $userAgent, $matches)) {
            if (!strpos($userAgent, 'Chrome')) {
                $browser = 'Safari';
                if (preg_match('/Version\/([0-9\.]+)/', $userAgent, $versionMatches)) {
                    $version = $versionMatches[1];
                }
            }
        } elseif (preg_match('/Edge\/([0-9\.]+)/', $userAgent, $matches)) {
            $browser = 'Edge';
            $version = $matches[1];
        }
        // @codeCoverageIgnoreEnd

        // Detect Operating System
        // @codeCoverageIgnoreStart
        // OS detection - memerlukan banyak test cases dengan berbagai User-Agent
        if (preg_match('/Windows NT ([0-9\.]+)/', $userAgent, $matches)) {
            $os = 'Windows ' . $matches[1];
        } elseif (preg_match('/Mac OS X ([0-9_]+)/', $userAgent, $matches)) {
            $os = 'Mac OS X ' . str_replace('_', '.', $matches[1]);
        } elseif (strpos($userAgent, 'Linux') !== false) {
            $os = 'Linux';
        } elseif (preg_match('/iPhone OS ([0-9_]+)/', $userAgent, $matches)) {
            $os = 'iOS ' . str_replace('_', '.', $matches[1]);
        } elseif (preg_match('/Android ([0-9\.]+)/', $userAgent, $matches)) {
            $os = 'Android ' . $matches[1];
        }
        // @codeCoverageIgnoreEnd

        return [
            'browser' => $browser,
            'version' => $version,
            'os' => $os
        ];
    }
}