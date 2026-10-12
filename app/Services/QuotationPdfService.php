<?php

namespace App\Services;

use App\Models\InvoiceSetting;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class QuotationPdfService
{
    /**
     * Render a quotation as a PDF binary string.
     */
    public function generate(Quotation $quotation): string
    {
        $quotation->loadMissing(['client', 'items', 'template']);
        $settings = InvoiceSetting::cached();
        $snapshot = $quotation->sender_snapshot ?? [];

        return Pdf::loadView('quotations.pdf', [
            'quotation' => $quotation,
            'settings' => $settings,
            'snapshot' => $snapshot,
            'groupedItems' => $quotation->items->groupBy(fn ($item) => $item->section ?: 'Item'),
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
