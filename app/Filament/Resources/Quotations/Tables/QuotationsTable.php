<?php

namespace App\Filament\Resources\Quotations\Tables;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Services\QuotationConverter;
use App\Services\QuotationDuplicator;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quotation_number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('template.name')
                    ->label('Jenis Proyek')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('client.name')
                    ->label('Klien')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('issue_date')
                    ->label('Terbit')
                    ->date()
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->label('Berlaku Hingga')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => [QuotationStatus::Draft, QuotationStatus::Cancelled],
                        'info' => QuotationStatus::Sent,
                        'success' => [QuotationStatus::Approved, QuotationStatus::Converted],
                        'danger' => [QuotationStatus::Rejected, QuotationStatus::Expired],
                    ]),
                TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(QuotationStatus::class),
                Filter::make('expired')
                    ->label('Kedaluarsa')
                    ->query(fn (Builder $query): Builder => $query->where('valid_until', '<', now()->format('Y-m-d'))->whereIn('status', [QuotationStatus::Draft, QuotationStatus::Sent])),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Quotation $record): bool => $record->status === QuotationStatus::Draft),
                Action::make('downloadPdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Quotation $record): string => route('quotations.pdf', $record))
                    ->openUrlInNewTab(),
                Action::make('print')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Quotation $record): string => route('quotations.print', $record))
                    ->openUrlInNewTab(),
                Action::make('markSent')
                    ->label('Tandai Terkirim')
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn (Quotation $record): bool => $record->status === QuotationStatus::Draft)
                    ->action(function (Quotation $record): void {
                        $record->forceFill(['status' => QuotationStatus::Sent, 'sent_at' => now()])->save();

                        Notification::make()->success()->title('Quotation ditandai terkirim.')->send();
                    }),
                Action::make('markApproved')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Quotation $record): bool => in_array($record->status, [QuotationStatus::Draft, QuotationStatus::Sent], true))
                    ->action(function (Quotation $record): void {
                        $record->forceFill(['status' => QuotationStatus::Approved, 'approved_at' => now()])->save();

                        Notification::make()->success()->title('Quotation disetujui klien.')->send();
                    }),
                Action::make('markRejected')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (Quotation $record): bool => in_array($record->status, [QuotationStatus::Draft, QuotationStatus::Sent], true))
                    ->requiresConfirmation()
                    ->action(function (Quotation $record): void {
                        $record->forceFill(['status' => QuotationStatus::Rejected])->save();

                        Notification::make()->success()->title('Quotation ditolak.')->send();
                    }),
                Action::make('convertToInvoice')
                    ->label('Buat Invoice')
                    ->icon('heroicon-o-document-text')
                    ->visible(fn (Quotation $record): bool => in_array($record->status, [QuotationStatus::Draft, QuotationStatus::Sent, QuotationStatus::Approved, QuotationStatus::Converted], true))
                    ->action(function (Quotation $record, QuotationConverter $converter): void {
                        $invoice = $converter->convert($record);

                        Notification::make()
                            ->success()
                            ->title("Invoice {$invoice->invoice_number} dibuat dari quotation ini.")
                            ->send();
                    }),
                Action::make('duplicate')
                    ->label('Duplikat')
                    ->icon('heroicon-o-square-2-stack')
                    ->action(function (Quotation $record, QuotationDuplicator $duplicator): void {
                        $copy = $duplicator->duplicate($record);

                        Notification::make()
                            ->success()
                            ->title("Quotation diduplikat menjadi {$copy->quotation_number}")
                            ->send();
                    }),
                DeleteAction::make()
                    ->visible(fn (Quotation $record): bool => $record->status === QuotationStatus::Draft),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
