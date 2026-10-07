<?php

namespace App\Models;

use App\Services\InvoiceCalculator;
use Database\Factories\InvoiceItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $invoice_id
 * @property string $description
 * @property float $qty
 * @property float $unit_price
 * @property float $line_total
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['invoice_id', 'description', 'qty', 'unit_price', 'line_total', 'sort_order'])]
class InvoiceItem extends Model
{
    /** @use HasFactory<InvoiceItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (InvoiceItem $item): void {
            $item->line_total = round((float) $item->qty * (float) $item->unit_price, 2);
        });

        static::saved(function (InvoiceItem $item): void {
            app(InvoiceCalculator::class)->recalculate($item->invoice);
        });

        static::deleted(function (InvoiceItem $item): void {
            app(InvoiceCalculator::class)->recalculate($item->invoice);
        });
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
