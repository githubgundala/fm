<?php

use App\Models\Invoice;
use App\Models\InvoiceSetting;
use App\Models\Payment;
use App\Models\Quotation;
use App\Services\InvoicePdfService;
use App\Services\QuotationPdfService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::get('payments/{payment}/slip', function (Payment $payment) {
        $payment->loadMissing(['invoice.client', 'invoice.items', 'invoice.payments']);
        $settings = InvoiceSetting::cached();

        return view('payments.slip', [
            'payment' => $payment,
            'invoice' => $payment->invoice,
            'settings' => $settings,
            'signatureUrl' => $settings->signature_path && Storage::disk('public')->exists($settings->signature_path)
                ? Storage::disk('public')->url($settings->signature_path)
                : null,
        ]);
    })->name('payments.slip');

    Route::get('invoices/{invoice}/pdf', function (Invoice $invoice, InvoicePdfService $pdf) {
        return response(
            $pdf->generate($invoice),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$invoice->invoice_number.'.pdf"',
            ]
        );
    })->name('invoices.pdf');

    Route::get('invoices/{invoice}/print', function (Invoice $invoice) {
        $invoice->loadMissing(['client', 'items', 'payments']);
        $settings = InvoiceSetting::cached();

        return view('invoices.print', [
            'invoice' => $invoice,
            'settings' => $settings,
            'logoUrl' => $settings->logo_path && Storage::disk('public')->exists($settings->logo_path)
                ? Storage::disk('public')->url($settings->logo_path)
                : null,
            'signatureUrl' => $settings->signature_path && Storage::disk('public')->exists($settings->signature_path)
                ? Storage::disk('public')->url($settings->signature_path)
                : null,
        ]);
    })->name('invoices.print');

    Route::get('quotations/{quotation}/pdf', function (Quotation $quotation, QuotationPdfService $pdf) {
        return response(
            $pdf->generate($quotation),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$quotation->quotation_number.'.pdf"',
            ]
        );
    })->name('quotations.pdf');

    Route::get('quotations/{quotation}/print', function (Quotation $quotation) {
        $quotation->loadMissing(['client', 'items', 'template']);
        $settings = InvoiceSetting::cached();

        return view('quotations.print', [
            'quotation' => $quotation,
            'settings' => $settings,
            'signatureUrl' => $settings->signature_path && Storage::disk('public')->exists($settings->signature_path)
                ? Storage::disk('public')->url($settings->signature_path)
                : null,
        ]);
    })->name('quotations.print');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
