<?php

namespace App\Services;

use setasign\Fpdi\Tcpdf\Fpdi;
use TCPDF;

class PDFService
{
    /**
     * Add signature watermark to a PDF (single QR for all signers)
     * 
     * @param string $inputPath Path to original PDF
     * @param string $outputPath Path to save signed PDF
     * @param array $signatures Array of signature details (now aggregated into single QR)
     * @return string Output path
     */
    public function addWatermarks(string $inputPath, string $outputPath, array $signatures): string
    {
        $pdf = new Fpdi();
        
        // Disable header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Try to load PDF, if encrypted, attempt to decrypt
        $decryptedPath = null;
        try {
            $pageCount = $pdf->setSourceFile($inputPath);
        } catch (\Exception $e) {
            // Check if PDF is encrypted
            if (str_contains($e->getMessage(), 'encrypted') || str_contains($e->getMessage(), 'Encryption')) {
                // Attempt to decrypt using qpdf
                $decryptedPath = $this->decryptPDF($inputPath);
                
                if ($decryptedPath) {
                    // Retry with decrypted file
                    try {
                        $pageCount = $pdf->setSourceFile($decryptedPath);
                        $inputPath = $decryptedPath; // Use decrypted version
                    } catch (\Exception $e2) {
                        throw new \Exception('PDF is owner-password protected. Please remove password protection first.');
                    }
                } else {
                    throw new \Exception('PDF decryption failed. Please upload an unprotected PDF.');
                }
            } else {
                throw $e;
            }
        }
        
        
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            
            // Get the size of the imported page
            $size = $pdf->getTemplateSize($templateId);
            
            // Add a page with the same orientation and size
            $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            
            // Use the imported page as template
            $pdf->useTemplate($templateId);
            
            // Add SINGLE signature block on the specified page (aggregated from all signers)
            if (!empty($signatures)) {
                $targetPage = $signatures[0]['page'] === 'last' ? $pageCount : ($signatures[0]['page'] ?? $pageCount);
                
                if ($pageNo == $targetPage) {
                    // Pass page size for auto-positioning
                    $aggregatedSig = $this->aggregateSignatures($signatures);
                    $aggregatedSig['page_width'] = $size['width'];
                    $aggregatedSig['page_height'] = $size['height'];
                    $this->drawSignature($pdf, $aggregatedSig);
                }
            }
        }
        
        $pdf->Output($outputPath, 'F');
        
        return $outputPath;
    }

    /**
     * Aggregate multiple signatures into single QR data
     * 
     * @param array $signatures Array of individual signatures
     * @return array Aggregated signature data with combined QR
     */
    protected function aggregateSignatures(array $signatures): array
    {
        // Extract signer info from all signatures
        $signers = [];
        $latestDate = null;
        
        foreach ($signatures as $sig) {
            $signerInfo = [
                'name' => $sig['signer_name'] ?? 'Unknown',
                'email' => $sig['signer_email'] ?? '',
                'signed_at' => $sig['date'] ?? date('Y-m-d H:i:s'),
                'certificate_serial' => $sig['cert_serial'] ?? ''
            ];
            $signers[] = $signerInfo;
            
            // Track latest signature date
            if (!$latestDate || strtotime($signerInfo['signed_at']) > strtotime($latestDate)) {
                $latestDate = $signerInfo['signed_at'];
            }
        }
        
        // Build QR data with all signers
        $qrData = [
            'document' => [
                'title' => $signatures[0]['document_title'] ?? 'Document',
                'filename' => $signatures[0]['document_filename'] ?? 'document.pdf',
                'hash' => $signatures[0]['document_hash'] ?? '',
                'signed_at' => $latestDate ?? date('Y-m-d H:i:s')
            ],
            'signers' => $signers,
            'verification' => [
                'verified_by' => 'Nusahire E-Sign',
                'verification_timestamp' => $latestDate ?? date('Y-m-d H:i:s'),
                'verification_method' => 'Digital Signature with X.509 Certificate'
            ]
        ];
        
        // Return aggregated signature with QR data
        return [
            'qr_data' => json_encode($qrData),
            'signer_names' => array_map(fn($s) => $s['name'], $signers),
            'date' => $latestDate ?? date('Y-m-d H:i:s'),
            'position' => $signatures[0]['position'] ?? 'bottom-right',
            'page' => $signatures[0]['page'] ?? 'last',
            'width' => $signatures[0]['width'] ?? 45,
            'height' => $signatures[0]['height'] ?? 40,
            'x' => $signatures[0]['x'] ?? null,
            'y' => $signatures[0]['y'] ?? null
        ];
    }
    
    protected function drawSignature(\TCPDF $pdf, array $sig)
    {
        // CRITICAL: Disable auto page break to keep all signature elements together
        $pdf->SetAutoPageBreak(false, 0);
        
        // Dimensions - Vertical layout (QR on top of text)
        $w = $sig['width'] ?? 45; // Width for vertical layout
        $h = $sig['height'] ?? 40; // Height for vertical layout (more space for QR + text)
        
        // Get page dimensions (passed from addWatermarks)
        $pageWidth = $sig['page_width'] ?? 210; // Default A4 width
        $pageHeight = $sig['page_height'] ?? 297; // Default A4 height
        
        // Calculate position based on 'position' parameter
        if (isset($sig['position']) && $sig['position'] === 'bottom-right') {
            // Bottom-right corner with margin
            $margin = 10;
            $x = $pageWidth - $w - $margin;
            $y = $pageHeight - $h - $margin;
            
            // If multiple signers, stack UPWARD (not horizontally)
            if (isset($sig['signer_index']) && $sig['signer_index'] > 0) {
                $stackOffset = ($h + 2) * $sig['signer_index']; // Stack upward
                $y -= $stackOffset;
            }
        } else {
            // Fallback to manual x,y
            $x = $sig['x'] ?? ($pageWidth - $w - 10);
            $y = $sig['y'] ?? ($pageHeight - $h - 20);
        }
        
        // Draw background box (Clean White)
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($x, $y, $w, $h, 'F');
        
        // Draw border (Subtle Gray)
        $pdf->SetLineStyle(['width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [150, 150, 150]]);
        $pdf->Rect($x, $y, $w, $h, 'D');
        
        // --- Vertical Layout ---
        // 1. "Digitally Signed by:" at top
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY($x + 2, $y + 2);
        $pdf->Cell($w - 4, 3, "Digitally Signed by:", 0, 1, 'C'); // Centered
        
        // 2. QR Code in middle (centered)
        $qrSize = 20; // QR code size
        $qrX = $x + ($w - $qrSize) / 2; // Center QR horizontally
        $qrY = $y + 7; // Position below text
        
        if (!empty($sig['qr_data'])) {
            $style = [
                'border' => 0,
                'vpadding' => 'auto',
                'hpadding' => 'auto',
                'fgcolor' => [0,0,0],
                'bgcolor' => false,
                'module_width' => 1, 
                'module_height' => 1
            ];
            // Position QR centered
            $pdf->write2DBarcode($sig['qr_data'], 'QRCODE,L', $qrX, $qrY, $qrSize, $qrSize, $style, 'N');
        }
        
        // 3. "Verified via Nusahire E-Sign" below QR
        $pdf->SetFont('helvetica', '', 6);
        $pdf->SetXY($x + 2, $y + 30);
        $pdf->Cell($w - 4, 3, "Verified via Nusahire E-Sign", 0, 1, 'C'); // Centered
        
        // 4. Date at bottom
        $pdf->SetFont('helvetica', 'I', 6);
        $pdf->SetXY($x + 2, $y + 34);
        $dateStr = "Date: " . ($sig['date'] ? date('d-m-Y', strtotime($sig['date'])) : date('d-m-Y'));
        $pdf->Cell($w - 4, 3, $dateStr, 0, 1, 'C'); // Centered
        
        // Re-enable auto page break
        $pdf->SetAutoPageBreak(true, 15);
    }
    
    /**
     * Decrypt an encrypted PDF using qpdf
     * 
     * @param string $inputPath Path to encrypted PDF
     * @return string|null Path to decrypted PDF or null if failed
     */
    private function decryptPDF(string $inputPath): ?string
    {
        // Check if qpdf is installed
        $qpdfPath = trim(shell_exec('which qpdf 2>/dev/null') ?? '');
        if (empty($qpdfPath)) {
            return null;
        }
        
        // Generate temp path for decrypted file
        $decryptedPath = storage_path('app/temp/' . uniqid('decrypted_') . '.pdf');
        
        // Ensure temp directory exists
        $tempDir = dirname($decryptedPath);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        // Run qpdf to decrypt (removes user password, not owner password)
        $command = sprintf(
            'qpdf --decrypt --password="" %s %s 2>&1',
            escapeshellarg($inputPath),
            escapeshellarg($decryptedPath)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($decryptedPath)) {
            return $decryptedPath;
        } else {
            return null;
        }
    }
}
