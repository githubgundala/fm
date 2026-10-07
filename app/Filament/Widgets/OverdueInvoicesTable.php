<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class OverdueInvoicesTable extends TableWidget
{
    protected static ?string $heading = 'Invoice Terlambat & Segera Jatuh Tempo';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Invoice::query()
                    ->with('client')
                    ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::PartiallyPaid, InvoiceStatus::Overdue])
                    ->where('due_date', '<=', now()->addDays(7)->format('Y-m-d'))
                    ->orderBy('due_date')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Nomor')
                    ->searchable(),
                TextColumn::make('client.name')
                    ->label('Klien'),
                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('amount_due')
                    ->label('Sisa')
                    ->money('IDR'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Invoice $record): string => InvoiceResource::getUrl('view', ['record' => $record])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
