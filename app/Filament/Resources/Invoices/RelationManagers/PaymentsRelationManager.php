<?php

namespace App\Filament\Resources\Invoices\RelationManagers;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentRecorder;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Pembayaran';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('payment_date')
                    ->label('Tanggal Bayar')
                    ->required(),
                TextInput::make('amount')
                    ->label('Nominal')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('Rp'),
                Select::make('method')
                    ->label('Metode')
                    ->options(PaymentMethod::class)
                    ->required(),
                TextInput::make('reference')
                    ->label('Referensi')
                    ->maxLength(100),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment_number')
            ->columns([
                TextColumn::make('payment_number')
                    ->label('Nomor')
                    ->searchable(),
                TextColumn::make('payment_date')
                    ->label('Tanggal')
                    ->date(),
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR'),
                TextColumn::make('method')
                    ->label('Metode')
                    ->badge(),
                TextColumn::make('reference')
                    ->label('Referensi')
                    ->placeholder('-'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Catat Pembayaran')
                    ->action(function (array $data, Action $action): void {
                        $owner = $this->ownerRecord;

                        if (! $owner instanceof Invoice) {
                            return;
                        }

                        $amount = $data['amount'] ?? 0;
                        $method = $data['method'] ?? '';
                        $paymentDate = $data['payment_date'] ?? null;
                        $reference = $data['reference'] ?? null;
                        $notes = $data['notes'] ?? null;

                        try {
                            app(PaymentRecorder::class)->record(
                                $owner,
                                is_numeric($amount) ? $amount : 0,
                                $method instanceof PaymentMethod || is_string($method) ? $method : '',
                                is_string($paymentDate) ? $paymentDate : null,
                                is_string($reference) ? $reference : null,
                                is_string($notes) ? $notes : null,
                            );
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Pembayaran gagal disimpan.')
                                ->body(collect($exception->errors())->flatten()->implode(' '))
                                ->send();

                            $action->halt();
                        }

                        Notification::make()
                            ->success()
                            ->title('Pembayaran dicatat.')
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('printSlip')
                    ->label('Slip')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Payment $record): string => route('payments.slip', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
