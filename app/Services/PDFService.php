<?php

namespace App\Services;

use setasign\Fpdi\Tcpdf\Fpdi;
use TCPDF;

class PDFService
{
    /**
     * Add multiple signature watermarks to a PDF
     * 
     * @param string $inputPath Path to original PDF
     * @param string $outputPath Path to save signed PDF
     * @param array $signatures Array of signature details: usually includes 'text', 'image_path' (optional), 'page', 'x', 'y'
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
            
            // Add signatures ONLY on the specified page
            foreach ($signatures as $sig) {
                // Handle 'last' page
                $targetPage = $sig['page'] === 'last' ? $pageCount : ($sig['page'] ?? $pageCount);
                
                if ($pageNo == $targetPage) {
                    // Pass page size for auto-positioning
                    $sig['page_width'] = $size['width'];
                    $sig['page_height'] = $size['height'];
                    $this->drawSignature($pdf, $sig);
                }
            }
        }
        
        $pdf->Output($outputPath, 'F');
        
        return $outputPath;
    }
    
    protected function drawSignature(\TCPDF $pdf, array $sig)
    {
        // CRITICAL: Disable auto page break to keep all signature elements together
        $pdf->SetAutoPageBreak(false, 0);
        
        // Dimensions
        $w = $sig['width'] ?? 55; // Compact width
        $h = $sig['height'] ?? 25; // Compact height
        
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
        
        // --- QR Code ---
        $qrSize = 20; // Smaller, more compact
        $qrMargin = 2;
        $textX = $x + $qrSize + ($qrMargin * 2); // Start text after QR
        
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
            // Position QR with padding
            $pdf->write2DBarcode($sig['qr_data'], 'QRCODE,L', $x + $qrMargin, $y + $qrMargin, $qrSize, $qrSize, $style, 'N');
        }
        
        // --- Text Layout ---
        
        // 1. "Digitally Signed by" (Small Label)
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY($textX, $y + 3);
        $pdf->Cell(0, 4, "Digitally Signed by:", 0, 0); // ln=0: no line break
        
        // 2. Signer Name (Bold & Larger) - Use Cell to avoid page breaks
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY($textX, $y + 7);
        $signerName = substr($sig['signer_name'] ?? "Signer", 0, 22); // Max 22 chars to fit in box
        $pdf->Cell($w - ($textX - $x) - 2, 8, $signerName, 0, 0, 'L');
        
        // 3. Info / Validity (Smaller)
        $pdf->SetFont('helvetica', '', 6);
        $pdf->SetXY($textX, $y + 16);
        $pdf->Cell(0, 3, "Verified via Nusahire E-Sign", 0, 0); // ln=0
        
        // 4. Date (Small Italic)
        $pdf->SetFont('helvetica', 'I', 6);
        $pdf->SetXY($textX, $y + 20);
        $pdf->Cell(0, 3, "Date: " . ($sig['date'] ?? date('Y-m-d H:i')), 0, 0); // ln=0
        
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
