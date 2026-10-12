<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Enums\QuotationStatus;
use App\Filament\Resources\Quotations\QuotationResource;
use App\Filament\Resources\Quotations\Schemas\QuotationForm;
use App\Models\InvoiceSetting;
use App\Services\QuotationCalculator;
use App\Services\QuotationNumberGenerator;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $defaults = QuotationForm::creationDefaults();
        $settings = InvoiceSetting::cached();

        $data['quotation_number'] = app(QuotationNumberGenerator::class)->next();
        $data['status'] = QuotationStatus::Draft;
        $data['valid_until'] ??= $defaults['valid_until'];
        $data['notes'] ??= $defaults['notes'];
        $data['terms'] ??= $defaults['terms'];
        $data['currency'] ??= 'IDR';
        $data['tax_rate'] ??= $settings->default_tax_rate;

        $snapshot = $settings->only(['company_name', 'company_address', 'company_phone', 'company_email']);
        $data['sender_snapshot'] = $snapshot;
        $data['sender_name'] ??= $settings->signature_name;
        $data['created_by'] = auth()->id();

        $calculator = app(QuotationCalculator::class);
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

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
