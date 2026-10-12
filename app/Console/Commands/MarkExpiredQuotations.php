<?php

namespace App\Console\Commands;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use Illuminate\Console\Command;

class MarkExpiredQuotations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotations:mark-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tandai quotation yang lewat masa berlaku sebagai expired.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $affected = Quotation::query()
            ->whereIn('status', [QuotationStatus::Draft, QuotationStatus::Sent])
            ->where('valid_until', '<', now()->format('Y-m-d'))
            ->update(['status' => QuotationStatus::Expired]);

        $this->info("{$affected} quotation ditandai expired.");

        return self::SUCCESS;
    }
}
