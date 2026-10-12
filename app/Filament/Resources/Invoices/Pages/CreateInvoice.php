<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Schemas\InvoiceForm;
use App\Models\Invoice;
use App\Services\InvoiceCalculator;
use App\Services\InvoiceNumberGenerator;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $defaults = InvoiceForm::creationDefaults();
        $generator = app(InvoiceNumberGenerator::class);

        $givenNumber = trim((string) ($data['invoice_number'] ?? ''));

        if ($givenNumber === '' || $givenNumber === $generator->peek()) {
            $data['invoice_number'] = $generator->next(InvoiceNumberGenerator::TYPE_INVOICE);
        } elseif (Invoice::query()->where('invoice_number', $givenNumber)->exists()) {
            // Stale preview (another invoice took the number first): fall back to a fresh one.
            $data['invoice_number'] = $generator->next(InvoiceNumberGenerator::TYPE_INVOICE);
        } else {
            $generator->syncCounter($givenNumber);
            $data['invoice_number'] = $givenNumber;
        }

        $data['status'] = InvoiceStatus::Draft;
        $data['due_date'] ??= $defaults['due_date'];
        $data['notes'] ??= $defaults['notes'];
        $data['terms'] ??= $defaults['terms'];
        $data['currency'] ??= 'IDR';
        $data['created_by'] = auth()->id();

        $calculator = app(InvoiceCalculator::class);
        $subtotal = 0.0;
        $items = $data['items'] ?? [];

        if (is_array($items)) {
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $subtotal += (float) ($item['qty'] ?? 0) * (float) ($item['unit_price'] ?? 0);
            }
        }

        $rawTaxRate = $data['tax_rate'] ?? null;

        $totals = $calculator->calculate(
            $subtotal,
            $data['discount_type'] ?? null,
            (float) ($data['discount_value'] ?? 0),
            is_numeric($rawTaxRate) ? (float) $rawTaxRate : null,
        );

        $data['subtotal'] = $totals['subtotal'];
        $data['tax_amount'] = $totals['taxAmount'];
        $data['grand_total'] = $totals['grandTotal'];
        $data['amount_paid'] = 0;
        $data['amount_due'] = $totals['grandTotal'];

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
