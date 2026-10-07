<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    /**
     * Render an invoice as a PDF binary string.
     */
    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing(['client', 'items', 'payments']);
        $settings = InvoiceSetting::cached();

        return Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'settings' => $settings,
            'logoBase64' => $this->imageBase64($settings->logo_path),
            'signatureBase64' => $this->imageBase64($settings->signature_path),
        ])
            ->setPaper('a4', 'portrait')
            ->output();
    }

    private function imageBase64(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($path));
    }
}
