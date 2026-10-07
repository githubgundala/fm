<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InvoiceStatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $outstanding = (float) Invoice::query()
            ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::PartiallyPaid, InvoiceStatus::Overdue])
            ->sum('amount_due');

        $overdue = Invoice::query()->where('status', InvoiceStatus::Overdue);
        $overdueAmount = (float) (clone $overdue)->sum('amount_due');
        $overdueCount = (clone $overdue)->count();

        $paidThisMonth = (float) Payment::query()
            ->whereYear('payment_date', now()->year)
            ->whereMonth('payment_date', now()->month)
            ->sum('amount');

        $draftCount = Invoice::query()->where('status', InvoiceStatus::Draft)->count();

        return [
            Stat::make('Piutang Berjalan', 'Rp '.number_format($outstanding, 0, ',', '.'))
                ->description('Total sisa tagihan aktif')
                ->color($outstanding > 0 ? 'warning' : 'success'),
            Stat::make('Terlambat', 'Rp '.number_format($overdueAmount, 0, ',', '.'))
                ->description("{$overdueCount} invoice overdue")
                ->color($overdueCount > 0 ? 'danger' : 'success'),
            Stat::make('Terbayar Bulan Ini', 'Rp '.number_format($paidThisMonth, 0, ',', '.'))
                ->description(now()->translatedFormat('F Y'))
                ->color('success'),
            Stat::make('Draft', (string) $draftCount)
                ->description('Invoice belum terkirim')
                ->color('gray'),
        ];
    }
}
