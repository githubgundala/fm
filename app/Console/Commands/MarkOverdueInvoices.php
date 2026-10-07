<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Console\Command;

class MarkOverdueInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:mark-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tandai invoice yang lewat jatuh tempo sebagai overdue.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $affected = Invoice::query()
            ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::PartiallyPaid])
            ->where('due_date', '<', now()->format('Y-m-d'))
            ->update(['status' => InvoiceStatus::Overdue]);

        $this->info("{$affected} invoice ditandai overdue.");

        return self::SUCCESS;
    }
}
