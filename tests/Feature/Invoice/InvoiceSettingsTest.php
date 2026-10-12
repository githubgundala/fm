<?php

use App\Filament\Pages\InvoiceSettingsPage;
use App\Filament\Resources\Invoices\Schemas\InvoiceForm;
use App\Models\InvoiceSetting;
use Illuminate\Support\Facades\Cache;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(superAdmin());
    Cache::forget(InvoiceSetting::CACHE_KEY);
});

it('persists settings and busts the cache', function (): void {
    $settings = InvoiceSetting::cached();

    livewire(InvoiceSettingsPage::class)
        ->fillForm([
            'company_name' => 'PT Maju Terus',
            'company_email' => 'billing@maju.co.id',
            'invoice_prefix' => 'INV',
            'payment_prefix' => 'PAY',
            'default_due_days' => 7,
            'default_tax_rate' => null,
            'allow_overpayment' => true,
            'email_subject_template' => 'Tagihan {invoice_number}',
            'email_body_template' => 'Halo {client_name}',
        ])
        ->call('save')
        ->assertNotified()
        ->assertHasNoFormErrors();

    expect(InvoiceSetting::cached()->company_name)->toBe('PT Maju Terus')
        ->and(InvoiceSetting::cached()->default_due_days)->toBe(7)
        ->and(InvoiceSetting::cached()->allow_overpayment)->toBeTrue();
});

it('validates required settings fields', function (): void {
    livewire(InvoiceSettingsPage::class)
        ->fillForm([
            'company_name' => null,
            'default_due_days' => -1,
        ])
        ->call('save')
        ->assertHasFormErrors(['company_name', 'default_due_days']);
});

it('uses updated defaults for new invoices', function (): void {
    InvoiceSetting::cached()->update(['default_due_days' => 30]);

    expect(InvoiceForm::creationDefaults()['due_date'])
        ->toBe(now()->addDays(30)->format('Y-m-d'));
});

it('menyimpan path gambar tanda tangan', function (): void {
    InvoiceSetting::cached()->update([
        'signature_path' => 'invoice-signatures/ttd.png',
        'signature_name' => 'Budi Santoso',
    ]);

    expect(InvoiceSetting::cached()->signature_path)->toBe('invoice-signatures/ttd.png')
        ->and(InvoiceSetting::cached()->signature_name)->toBe('Budi Santoso');
});

it('menyimpan nama penerima slip pembayaran', function (): void {
    livewire(InvoiceSettingsPage::class)
        ->fillForm([
            'slip_receiver_name' => 'Sari Kasir',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(InvoiceSetting::cached()->slip_receiver_name)->toBe('Sari Kasir');
});
