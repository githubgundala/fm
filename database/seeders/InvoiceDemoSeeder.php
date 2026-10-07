<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceSetting;
use App\Services\InvoiceCalculator;
use App\Services\PaymentRecorder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class InvoiceDemoSeeder extends Seeder
{
    /**
     * Seed demo clients, invoices, and payments. Idempotent-ish: skips when demo data exists.
     */
    public function run(): void
    {
        Cache::forget(InvoiceSetting::CACHE_KEY);
        InvoiceSetting::cached()->update([
            'company_name' => 'PT Contoh Sejahtera',
            'company_address' => 'Jl. Merdeka No. 45, Jakarta',
            'company_phone' => '(021) 555-0123',
            'company_email' => 'billing@contoh.co.id',
            'default_due_days' => 14,
            'default_terms' => 'Pembayaran maksimal 14 hari setelah invoice diterbitkan.',
        ]);

        if (Client::where('email', 'budi@maju.co.id')->exists()) {
            return;
        }

        $clients = collect([
            ['name' => 'Budi Santoso', 'company' => 'PT Maju Bersama', 'email' => 'budi@maju.co.id'],
            ['name' => 'Siti Rahayu', 'company' => 'CV Berkah Jaya', 'email' => 'siti@berkah.co.id'],
            ['name' => 'Andi Pratama', 'company' => null, 'email' => 'andi.pratama@mail.co.id'],
        ])->map(fn (array $data): Client => Client::create($data));

        $recorder = app(PaymentRecorder::class);

        // 1. Draft invoice.
        $this->makeInvoice($clients[0], InvoiceStatus::Draft, [
            ['Jasa desain logo', 1, 2_500_000],
        ]);

        // 2. Sent invoice.
        $this->makeInvoice($clients[1], InvoiceStatus::Sent, [
            ['Jasa pembuatan website', 1, 15_000_000],
            ['Maintenance bulan pertama', 1, 1_500_000],
        ]);

        // 3. Partially paid (DP 30%).
        $partial = $this->makeInvoice($clients[0], InvoiceStatus::Sent, [
            ['Jasa konsultasi IT', 10, 750_000],
        ]);
        $recorder->record($partial->refresh(), 2_250_000, PaymentMethod::Transfer, notes: 'DP 30%');
        app(InvoiceCalculator::class)->recalculate($partial->refresh());

        // 4. Paid in full.
        $paid = $this->makeInvoice($clients[2], InvoiceStatus::Sent, [
            ['Jasa fotografi acara', 1, 5_000_000],
        ]);
        $recorder->record($paid->refresh(), 5_000_000, PaymentMethod::Qris);
        app(InvoiceCalculator::class)->recalculate($paid->refresh());

        // 5. Overdue invoice.
        $overdue = $this->makeInvoice($clients[1], InvoiceStatus::Sent, [
            ['Langganan software tahunan', 1, 12_000_000],
        ], now()->subDays(30), now()->subDays(16));
        $overdue->forceFill(['status' => InvoiceStatus::Overdue])->save();
    }

    /**
     * @param  array<int, array{0: string, 1: float, 2: float}>  $items
     */
    private function makeInvoice(
        Client $client,
        InvoiceStatus $status,
        array $items,
        ?\DateTimeInterface $issueDate = null,
        ?\DateTimeInterface $dueDate = null,
    ): Invoice {
        /** @var Invoice $invoice */
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => $status,
            'issue_date' => ($issueDate ?? now())->format('Y-m-d'),
            'due_date' => ($dueDate ?? now()->addDays(14))->format('Y-m-d'),
        ]);

        foreach ($items as $index => [$description, $qty, $unitPrice]) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $description,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => round($qty * $unitPrice, 2),
                'sort_order' => $index,
            ]);
        }

        // Seeders run without model events, so recalculate explicitly.
        app(InvoiceCalculator::class)->recalculate($invoice->refresh());

        return $invoice->refresh();
    }
}
