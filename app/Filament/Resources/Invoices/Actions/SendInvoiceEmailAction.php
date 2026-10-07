<?php

namespace App\Filament\Resources\Invoices\Actions;

use App\Jobs\SendInvoiceEmailJob;
use App\Models\Invoice;
use App\Support\InvoiceTemplate;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class SendInvoiceEmailAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Kirim Email')
            ->icon('heroicon-o-envelope')
            ->color('info')
            ->schema([
                TextInput::make('to')
                    ->label('Kepada')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('cc')
                    ->label('Cc')
                    ->email()
                    ->nullable()
                    ->maxLength(255),
                TextInput::make('subject')
                    ->label('Subjek')
                    ->required()
                    ->maxLength(255),
                Textarea::make('body')
                    ->label('Isi Email')
                    ->required()
                    ->rows(8)
                    ->columnSpanFull(),
            ])
            ->fillForm(function (Invoice $record): array {
                $defaults = InvoiceTemplate::defaults($record->loadMissing('client'));

                return [
                    'to' => $record->client->email,
                    'cc' => null,
                    'subject' => $defaults['subject'],
                    'body' => $defaults['body'],
                ];
            })
            ->action(function (array $data, Invoice $record): void {
                SendInvoiceEmailJob::dispatch(
                    $record,
                    $data['to'],
                    $data['cc'] ?? null,
                    $data['subject'],
                    $data['body'],
                );
            })
            ->after(function (): void {
                Notification::make()
                    ->success()
                    ->title('Email invoice masuk antrean pengiriman.')
                    ->send();
            });
    }
}
